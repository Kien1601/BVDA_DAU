import * as THREE from 'three';
import './cityScene.css';

/**
 * Cảnh "thành phố" dùng chung cho trang chủ (high) và trang đăng nhập (low).
 * Có hai phiên bản: đêm (chế độ tối) và ngày (chế độ sáng), chuyển dần trong 1,2 giây.
 *
 *   - Lưới đường và các tòa nhà. Đêm: cửa sổ phát sáng. Ngày: cửa kính, nắng.
 *   - Xe chạy trên đường, để lại vệt như tín hiệu GPS. Xe đỏ son = xe đang cho thuê,
 *     định kỳ phát vòng sóng = gói tin vị trí. Cột đỏ son đánh dấu cửa hàng.
 *
 * Cách dùng:
 *   const city = createCityScene(host, { quality: 'high' | 'low', mode: 'night' | 'day' });
 *   city.setProgress(0..1);          // trang chủ: camera hạ dần theo thao tác cuộn
 *   city.setMode('day' | 'night');   // đổi ngày/đêm, chuyển dần
 *   city.dispose();                  // giải phóng toàn bộ tài nguyên GPU
 *
 * Vì sao ban ngày phải đổi cách hòa màu: ban đêm xe và vệt dùng AdditiveBlending (cộng sáng)
 * để phát quang. Trên nền trời sáng, cộng thêm sáng thì không thấy gì, nên ban ngày dùng
 * NormalBlending, và vệt mờ dần về TRONG SUỐT (kênh alpha) chứ không về màu đen.
 */

const PRESETS = {
    high: { blocks: 14, vehicles: 70, trail: 16, dpr: 1.75, pulses: 8, beacons: 4 },
    low:  { blocks: 9,  vehicles: 30, trail: 10, dpr: 1.25, pulses: 4, beacons: 3 },
};

const BLOCK = 10;
const STREET = 2.4;
const TRAIL_STEP = 0.7;
const TURN_CHANCE = 0.35;
const CAR_Y = 0.6;
const TRANSITION = 1.2;   // giây

const C = (hex) => new THREE.Color(hex);

const PALETTES = {
    night: {
        sky: C(0x05070a), fogScale: 1, ground: C(0x070b0e), street: C(0x1a2731),
        wall: C(0x0c1218), roof: C(0x080c10), windowGlow: 0.75,
        hemiSky: C(0x2c4250), hemiGround: C(0x05070a), hemi: 0.9,
        sun: C(0x9cc8d4), sunPower: 0.45, sunPos: new THREE.Vector3(-40, 80, 30),
        cars: [C(0xdfe7e0), C(0x86c6d2), C(0xe0231c)],
        headSize: 2.2, pulseMax: 0.8, beaconBase: 0.22, beaconAmp: 0.08, halo: 1,
    },
    day: {
        sky: C(0xe8eef0), fogScale: 0.8, ground: C(0xd5dcd9), street: C(0xaab6b4),
        wall: C(0xeef1ee), roof: C(0xc4ccca), windowGlow: 0,
        hemiSky: C(0xffffff), hemiGround: C(0xa9b4b1), hemi: 1.5,
        sun: C(0xfff1dc), sunPower: 1.3, sunPos: new THREE.Vector3(60, 90, 40),
        cars: [C(0x1b232a), C(0x1f6f7a), C(0xe0231c)],
        headSize: 1.8, pulseMax: 0.6, beaconBase: 0.16, beaconAmp: 0.05, halo: 0.55,
    },
};

const lerp = (a, b, t) => a + (b - a) * t;
const clamp01 = (v) => Math.min(1, Math.max(0, v));
const smoothstep = (t) => t * t * (3 - 2 * t);
const damp = (current, target, rate, dt) => lerp(current, target, 1 - Math.exp(-rate * dt));

function mulberry32(seed) {
    return function () {
        seed |= 0; seed = (seed + 0x6D2B79F5) | 0;
        let t = Math.imul(seed ^ (seed >>> 15), 1 | seed);
        t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}

function canvasTexture(width, height, draw) {
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    draw(canvas.getContext('2d'), width, height);
    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;
    return texture;
}

/**
 * Hai texture mặt tường dùng chung một lưới cửa sổ:
 *   day   — tường sáng, mọi ô là kính xám xanh (dùng làm map)
 *   night — nền đen, chỉ một phần ô sáng đèn (dùng làm emissiveMap)
 */
function windowTextures(seed) {
    const rnd = mulberry32(seed);
    const cols = 4, rows = 12;
    const cells = [];
    for (let r = 0; r < rows; r++) {
        for (let c = 0; c < cols; c++) {
            const v = rnd();
            cells.push({ r, c, lit: v < 0.38, v, alpha: 0.35 + rnd() * 0.65, shade: rnd() });
        }
    }

    const rect = (ctx, w, h, cell) => {
        const cw = w / cols, rh = h / rows;
        ctx.fillRect(cell.c * cw + cw * 0.22, cell.r * rh + rh * 0.25, cw * 0.56, rh * 0.5);
    };

    const night = canvasTexture(64, 128, (ctx, w, h) => {
        ctx.fillStyle = '#000';
        ctx.fillRect(0, 0, w, h);
        cells.filter((cell) => cell.lit).forEach((cell) => {
            ctx.globalAlpha = cell.alpha;
            ctx.fillStyle = cell.v < 0.08
                ? 'rgb(150,215,228)'
                : `rgb(255,${170 + ((cell.v * 180) | 0)},${100 + ((cell.v * 120) | 0)})`;
            rect(ctx, w, h, cell);
        });
        ctx.globalAlpha = 1;
    });

    const day = canvasTexture(64, 128, (ctx, w, h) => {
        ctx.fillStyle = '#f2f4f1';
        ctx.fillRect(0, 0, w, h);
        cells.forEach((cell) => {
            const s = cell.shade;
            ctx.fillStyle = `rgb(${(159 - 32 * s) | 0},${(179 - 28 * s) | 0},${(188 - 25 * s) | 0})`;
            rect(ctx, w, h, cell);
        });
    });

    return { night, day };
}

function glowTexture() {
    return canvasTexture(64, 64, (ctx, w, h) => {
        const g = ctx.createRadialGradient(w / 2, h / 2, 0, w / 2, h / 2, w / 2);
        g.addColorStop(0, 'rgba(255,255,255,1)');
        g.addColorStop(0.25, 'rgba(255,255,255,.55)');
        g.addColorStop(1, 'rgba(255,255,255,0)');
        ctx.fillStyle = g;
        ctx.fillRect(0, 0, w, h);
    });
}

export function createCityScene(host, options = {}) {
    const coarse = matchMedia('(pointer: coarse)').matches;
    const finePointer = matchMedia('(pointer: fine)').matches;
    const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const P = PRESETS[coarse ? 'low' : options.quality] ?? PRESETS.high;
    const noop = { setProgress() {}, setMode() {}, dispose() {} };

    /* ---------------------------------------------------------- renderer */
    let renderer;
    try {
        renderer = new THREE.WebGLRenderer({ antialias: true, powerPreference: 'high-performance' });
        if (!renderer.getContext()) throw new Error('Không có WebGL');
    } catch (error) {
        host.classList.add('is-fallback');
        return noop;
    }

    const canvas = renderer.domElement;
    canvas.setAttribute('aria-hidden', 'true');
    host.appendChild(canvas);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, P.dpr));

    const rnd = mulberry32(2026);
    const N = P.blocks;
    const half = (N * BLOCK) / 2;
    const S = Array.from({ length: N + 1 }, (_, k) => -half + k * BLOCK);
    const baseFog = 0.9 / (half * 2);

    const scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(0x05070a, baseFog);
    const camera = new THREE.PerspectiveCamera(42, 1, 0.5, 600);
    const textures = [];

    /* ---------------------------------------------------------- ánh sáng */
    const hemi = new THREE.HemisphereLight(0xffffff, 0x000000, 1);
    const sun = new THREE.DirectionalLight(0xffffff, 1);
    scene.add(hemi, sun);

    /* ---------------------------------------------------------- mặt đất, đường */
    const ground = new THREE.Mesh(
        new THREE.PlaneGeometry(half * 8, half * 8),
        new THREE.MeshLambertMaterial({ color: 0x000000 }),
    );
    ground.rotation.x = -Math.PI / 2;
    scene.add(ground);

    const streetLines = [];
    const far = half + BLOCK;
    S.forEach((s) => {
        [s - STREET / 2, s + STREET / 2].forEach((e) => {
            streetLines.push(e, 0.02, -far, e, 0.02, far);
            streetLines.push(-far, 0.02, e, far, 0.02, e);
        });
    });
    const streetGeo = new THREE.BufferGeometry();
    streetGeo.setAttribute('position', new THREE.Float32BufferAttribute(streetLines, 3));
    const streetMat = new THREE.LineBasicMaterial({ color: 0x000000 });
    scene.add(new THREE.LineSegments(streetGeo, streetMat));

    /* ---------------------------------------------------------- tòa nhà */
    const lots = [];
    for (let i = 0; i < N; i++) {
        for (let j = 0; j < N; j++) {
            const x0 = S[i] + STREET / 2, z0 = S[j] + STREET / 2, sub = (BLOCK - STREET) / 2;
            for (let a = 0; a < 2; a++) {
                for (let b = 0; b < 2; b++) {
                    if (rnd() < 0.15) continue;
                    const cx = x0 + sub * (a + 0.5), cz = z0 + sub * (b + 0.5);
                    const centre = 1 - Math.min(1, Math.hypot(cx, cz) / half);
                    const height = 1.5 + Math.pow(rnd(), 2.2) * (6 + 24 * centre);
                    lots.push([cx, cz, sub * (0.62 + rnd() * 0.26), height, sub * (0.62 + rnd() * 0.26)]);
                }
            }
        }
    }

    const windows = windowTextures(91);
    textures.push(windows.night, windows.day);
    /* map (kính ban ngày) luôn gắn sẵn: ban đêm màu tường tối nhân với nó vẫn tối,
       nên không phải đổi map giữa chừng (tránh giật hình lúc chuyển cảnh) */
    const wall = new THREE.MeshLambertMaterial({
        color: 0xffffff, map: windows.day,
        emissive: 0xffffff, emissiveMap: windows.night, emissiveIntensity: 0,
    });
    const roof = new THREE.MeshLambertMaterial({ color: 0x000000 });
    const buildings = new THREE.InstancedMesh(
        new THREE.BoxGeometry(1, 1, 1).translate(0, 0.5, 0),
        [wall, wall, roof, roof, wall, wall],
        lots.length,
    );
    const matrix = new THREE.Matrix4(), rotation = new THREE.Quaternion();
    const at = new THREE.Vector3(), size = new THREE.Vector3();
    lots.forEach(([x, z, w, h, d], i) => {
        matrix.compose(at.set(x, 0, z), rotation, size.set(w, h, d));
        buildings.setMatrixAt(i, matrix);
    });
    scene.add(buildings);

    /* ---------------------------------------------------------- xe */
    const vehicles = [];
    for (let i = 0; i < P.vehicles; i++) {
        const kCur = (rnd() * (N + 1)) | 0;
        let dir = rnd() < 0.5 ? -1 : 1;
        if (kCur + dir < 0 || kCur + dir > N) dir = -dir;
        const rented = rnd() < 0.16;
        vehicles.push({
            axis: rnd() < 0.5 ? 0 : 1,
            fixedIdx: (rnd() * (N + 1)) | 0,
            kCur, dir, ni: kCur + dir,
            pos: S[kCur],
            speed: 4 + rnd() * 7,
            rented,
            kind: rented ? 2 : (rnd() < 0.5 ? 0 : 1),   // 0 trắng/đen, 1 xanh ngọc, 2 đỏ son
            color: new THREE.Color(),
            trail: [],
        });
    }

    const worldOf = (v, out) => (v.axis === 0
        ? out.set(S[v.fixedIdx], CAR_Y, v.pos)
        : out.set(v.pos, CAR_Y, S[v.fixedIdx]));

    function drive(v, distance) {
        while (distance > 0) {
            const target = S[v.ni];
            const remain = Math.abs(target - v.pos);
            if (distance < remain) {
                v.pos += v.dir * distance;
                return;
            }
            distance -= remain;
            v.pos = target;
            v.kCur = v.ni;
            if (Math.random() < TURN_CHANCE) {
                const oldFixed = v.fixedIdx;
                v.axis = 1 - v.axis;
                v.fixedIdx = v.kCur;
                v.kCur = oldFixed;
                v.pos = S[oldFixed];
                v.dir = Math.random() < 0.5 ? -1 : 1;
            }
            v.ni = v.kCur + v.dir;
            if (v.ni < 0 || v.ni > N) {
                v.dir = -v.dir;
                v.ni = v.kCur + v.dir;
            }
        }
    }

    const glow = glowTexture();
    textures.push(glow);

    const headPos = new Float32Array(vehicles.length * 3);
    const headCol = new Float32Array(vehicles.length * 3);
    const headGeo = new THREE.BufferGeometry();
    headGeo.setAttribute('position', new THREE.BufferAttribute(headPos, 3));
    headGeo.setAttribute('color', new THREE.BufferAttribute(headCol, 3));
    const heads = new THREE.Points(headGeo, new THREE.PointsMaterial({
        size: 2.2, map: glow, vertexColors: true, transparent: true, depthWrite: false,
    }));
    heads.frustumCulled = false;
    scene.add(heads);

    /* vệt: màu RGBA, alpha giảm dần về đuôi (mờ về trong suốt) */
    const T = P.trail, SEGS = T - 1;
    const fade = (s) => Math.pow(1 - s / SEGS, 1.6) * 0.9;
    const trailPos = new Float32Array(vehicles.length * SEGS * 6);
    const trailCol = new Float32Array(vehicles.length * SEGS * 8);
    const trailGeo = new THREE.BufferGeometry();
    trailGeo.setAttribute('position', new THREE.BufferAttribute(trailPos, 3));
    trailGeo.setAttribute('color', new THREE.BufferAttribute(trailCol, 4));
    const trails = new THREE.LineSegments(trailGeo, new THREE.LineBasicMaterial({
        vertexColors: true, transparent: true, depthWrite: false,
    }));
    trails.frustumCulled = false;
    scene.add(trails);

    /* tô lại màu đầu xe và vệt theo màu hiện tại của từng xe */
    function paintVehicles() {
        vehicles.forEach((v, i) => {
            v.color.toArray(headCol, i * 3);
            let o = i * SEGS * 8;
            for (let s = 0; s < SEGS; s++) {
                for (const f of [fade(s), fade(s + 1)]) {
                    trailCol[o++] = v.color.r;
                    trailCol[o++] = v.color.g;
                    trailCol[o++] = v.color.b;
                    trailCol[o++] = f;
                }
            }
        });
        headGeo.attributes.color.needsUpdate = true;
        trailGeo.attributes.color.needsUpdate = true;
    }

    const tmp = new THREE.Vector3();
    vehicles.forEach((v) => {
        worldOf(v, tmp);
        for (let k = 0; k < T; k++) v.trail.push(tmp.clone());
    });

    function writeVehicle(v, i) {
        worldOf(v, tmp);
        tmp.toArray(headPos, i * 3);
        v.trail[0].copy(tmp);
        if (tmp.distanceTo(v.trail[1]) >= TRAIL_STEP) {
            const recycled = v.trail.pop();
            v.trail.splice(1, 0, recycled.copy(tmp));
        }
        let o = i * SEGS * 6;
        for (let s = 0; s < SEGS; s++) {
            const a = v.trail[s], b = v.trail[s + 1];
            trailPos[o++] = a.x; trailPos[o++] = a.y; trailPos[o++] = a.z;
            trailPos[o++] = b.x; trailPos[o++] = b.y; trailPos[o++] = b.z;
        }
    }

    /* ---------------------------------------------------------- vòng sóng GPS */
    const ringGeo = new THREE.RingGeometry(0.85, 1, 48).rotateX(-Math.PI / 2);
    const pulses = Array.from({ length: P.pulses }, () => {
        const mesh = new THREE.Mesh(ringGeo, new THREE.MeshBasicMaterial({
            color: 0xe0231c, transparent: true, opacity: 0, depthWrite: false,
        }));
        mesh.visible = false;
        scene.add(mesh);
        return { mesh, t: 1 };
    });
    let pulseTimer = 0, pulseIndex = 0;

    function emitPulse() {
        const pool = vehicles.filter((v) => v.rented);
        const list = pool.length ? pool : vehicles;
        const v = list[(Math.random() * list.length) | 0];
        const p = pulses[pulseIndex];
        pulseIndex = (pulseIndex + 1) % pulses.length;
        worldOf(v, p.mesh.position);
        p.mesh.position.y = 0.08;
        p.mesh.material.color.copy(v.color);
        p.mesh.visible = true;
        p.t = 0;
    }

    /* ---------------------------------------------------------- cột cửa hàng */
    const beaconGeo = new THREE.CylinderGeometry(0.18, 0.18, 60, 10, 1, true).translate(0, 30, 0);
    const beacons = [];
    for (let b = 0; b < P.beacons; b++) {
        const x = S[1 + ((rnd() * (N - 1)) | 0)], z = S[1 + ((rnd() * (N - 1)) | 0)];
        const beam = new THREE.Mesh(beaconGeo, new THREE.MeshBasicMaterial({
            color: 0xe0231c, transparent: true, opacity: 0.28, depthWrite: false,
        }));
        beam.position.set(x, 0, z);
        const halo = new THREE.Sprite(new THREE.SpriteMaterial({
            map: glow, color: 0xe0231c, transparent: true, depthWrite: false,
        }));
        halo.position.set(x, 1, z);
        halo.scale.setScalar(7);
        scene.add(beam, halo);
        beacons.push({ beam, halo, phase: rnd() * Math.PI * 2 });
    }

    /* ---------------------------------------------------------- bảng màu ngày/đêm */
    const glowMaterials = [
        heads.material, trails.material,
        ...pulses.map((p) => p.mesh.material),
        ...beacons.flatMap((b) => [b.beam.material, b.halo.material]),
    ];
    let additive = null;
    function setBlending(useAdditive) {
        if (additive === useAdditive) return;   // chỉ đổi một lần khi qua mốc giữa
        additive = useAdditive;
        glowMaterials.forEach((m) => {
            m.blending = useAdditive ? THREE.AdditiveBlending : THREE.NormalBlending;
            m.needsUpdate = true;
        });
    }

    const NIGHT = PALETTES.night, DAY = PALETTES.day;
    const sky = new THREE.Color();
    let pulseMax = NIGHT.pulseMax, beaconBase = NIGHT.beaconBase, beaconAmp = NIGHT.beaconAmp;

    /** m: 0 = đêm, 1 = ngày (đã làm mượt) */
    function applyPalette(m) {
        sky.lerpColors(NIGHT.sky, DAY.sky, m);
        renderer.setClearColor(sky, 1);
        scene.fog.color.copy(sky);
        scene.fog.density = baseFog * lerp(NIGHT.fogScale, DAY.fogScale, m);

        ground.material.color.lerpColors(NIGHT.ground, DAY.ground, m);
        streetMat.color.lerpColors(NIGHT.street, DAY.street, m);
        wall.color.lerpColors(NIGHT.wall, DAY.wall, m);
        wall.emissiveIntensity = lerp(NIGHT.windowGlow, DAY.windowGlow, m);
        roof.color.lerpColors(NIGHT.roof, DAY.roof, m);

        hemi.color.lerpColors(NIGHT.hemiSky, DAY.hemiSky, m);
        hemi.groundColor.lerpColors(NIGHT.hemiGround, DAY.hemiGround, m);
        hemi.intensity = lerp(NIGHT.hemi, DAY.hemi, m);
        sun.color.lerpColors(NIGHT.sun, DAY.sun, m);
        sun.intensity = lerp(NIGHT.sunPower, DAY.sunPower, m);
        sun.position.lerpVectors(NIGHT.sunPos, DAY.sunPos, m);

        heads.material.size = lerp(NIGHT.headSize, DAY.headSize, m);
        vehicles.forEach((v) => v.color.lerpColors(NIGHT.cars[v.kind], DAY.cars[v.kind], m));
        paintVehicles();

        pulseMax = lerp(NIGHT.pulseMax, DAY.pulseMax, m);
        beaconBase = lerp(NIGHT.beaconBase, DAY.beaconBase, m);
        beaconAmp = lerp(NIGHT.beaconAmp, DAY.beaconAmp, m);
        beacons.forEach((b) => { b.halo.material.opacity = lerp(NIGHT.halo, DAY.halo, m); });

        setBlending(m < 0.5);
    }

    let mix = options.mode === 'day' ? 1 : 0;
    let mixTarget = mix;

    /* ---------------------------------------------------------- camera */
    const cam = { angle: Math.PI * 0.25, target: 0, progress: 0, mx: 0, my: 0, tmx: 0, tmy: 0 };

    function placeCamera() {
        const p = cam.progress;
        const radius = lerp(half * 1.45, half * 0.62, p);
        const height = lerp(half * 1.05, half * 0.24, p);
        camera.position.set(
            Math.cos(cam.angle) * radius + cam.mx * 4,
            height + cam.my * 3,
            Math.sin(cam.angle) * radius,
        );
        camera.lookAt(cam.mx * 2, lerp(0, 3, p), 0);
    }

    function setProgress(value) {
        cam.target = clamp01(value);
        if (!running) {
            cam.progress = cam.target;
            render();
        }
    }

    function setMode(mode, { instant = false } = {}) {
        mixTarget = mode === 'day' ? 1 : 0;
        // không có vòng lặp chạy (giảm chuyển động, tab ẩn, ngoài màn hình): đổi ngay
        if (instant || reduce || !running) {
            mix = mixTarget;
            applyPalette(mix);
            render();
        }
    }

    const onPointer = (e) => {
        cam.tmx = (e.clientX / window.innerWidth) * 2 - 1;
        cam.tmy = -((e.clientY / window.innerHeight) * 2 - 1);
    };
    if (finePointer && !reduce) window.addEventListener('pointermove', onPointer, { passive: true });

    /* ---------------------------------------------------------- vòng lặp */
    let raf = 0, running = false, visible = true, dead = false, last = 0, clock = 0;

    function update(dt) {
        clock += dt;
        cam.angle += dt * 0.025;
        cam.progress = damp(cam.progress, cam.target, 4, dt);
        cam.mx = damp(cam.mx, cam.tmx, 2.5, dt);
        cam.my = damp(cam.my, cam.tmy, 2.5, dt);

        if (mix !== mixTarget) {
            const step = dt / TRANSITION;
            mix = mixTarget > mix ? Math.min(mixTarget, mix + step) : Math.max(mixTarget, mix - step);
            applyPalette(smoothstep(mix));
        }

        vehicles.forEach((v, i) => {
            drive(v, v.speed * dt);
            writeVehicle(v, i);
        });
        headGeo.attributes.position.needsUpdate = true;
        trailGeo.attributes.position.needsUpdate = true;

        pulseTimer += dt;
        if (pulseTimer > 0.9) { pulseTimer = 0; emitPulse(); }
        pulses.forEach((p) => {
            if (p.t >= 1) { p.mesh.visible = false; return; }
            p.t = Math.min(1, p.t + dt / 1.6);
            p.mesh.scale.setScalar(1 + p.t * 5);
            p.mesh.material.opacity = (1 - p.t) * pulseMax;
        });

        beacons.forEach((b) => {
            b.beam.material.opacity = beaconBase + Math.sin(clock * 1.4 + b.phase) * beaconAmp;
        });
    }

    function render() {
        placeCamera();
        renderer.render(scene, camera);
    }

    function frame(now) {
        if (!running) return;
        const dt = Math.min((now - last) / 1000, 0.05);
        last = now;
        update(dt);
        render();
        raf = requestAnimationFrame(frame);
    }

    function start() {
        if (dead || running || reduce || !visible || document.hidden) return;
        running = true;
        last = performance.now();
        raf = requestAnimationFrame(frame);
    }

    function stop() {
        running = false;
        cancelAnimationFrame(raf);
    }

    /* ---------------------------------------------------------- kích thước, hiển thị */
    function resize() {
        const w = Math.max(1, host.clientWidth), h = Math.max(1, host.clientHeight);
        renderer.setSize(w, h, false);
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        if (!running) render();
    }

    const resizeObserver = new ResizeObserver(resize);
    resizeObserver.observe(host);

    const intersection = new IntersectionObserver((entries) => {
        visible = entries[entries.length - 1]?.isIntersecting ?? true;
        visible ? start() : stop();
    });
    intersection.observe(host);

    const onVisibility = () => (document.hidden ? stop() : start());
    document.addEventListener('visibilitychange', onVisibility);

    const onContextLost = (e) => {
        e.preventDefault();
        stop();
        host.classList.add('is-fallback');
    };
    canvas.addEventListener('webglcontextlost', onContextLost);

    /* ---------------------------------------------------------- dọn dẹp */
    function dispose() {
        if (dead) return;
        dead = true;
        stop();
        resizeObserver.disconnect();
        intersection.disconnect();
        document.removeEventListener('visibilitychange', onVisibility);
        window.removeEventListener('pointermove', onPointer);
        canvas.removeEventListener('webglcontextlost', onContextLost);
        scene.traverse((object) => {
            object.geometry?.dispose();
            const m = object.material;
            (Array.isArray(m) ? m : m ? [m] : []).forEach((material) => material.dispose());
        });
        textures.forEach((t) => t.dispose());
        renderer.dispose();
        canvas.remove();
    }

    /* ---------------------------------------------------------- khởi động */
    applyPalette(mix);
    vehicles.forEach(writeVehicle);
    resize();
    start();

    return { setProgress, setMode, dispose };
}