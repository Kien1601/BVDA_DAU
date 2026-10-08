import * as THREE from 'three';
import './cityScene.css';

/**
 * Cảnh "thành phố đêm", dùng chung cho trang chủ khách (high) và trang đăng nhập (low).
 *
 *   - Lưới đường và các tòa nhà có cửa sổ sáng.
 *   - Xe là điểm sáng chạy trên đường, để lại vệt sáng như tín hiệu GPS.
 *     Xe màu đỏ son là xe đang cho thuê; định kỳ phát vòng sóng = gói tin vị trí.
 *   - Cột sáng đỏ son đánh dấu cửa hàng.
 *
 * Cách dùng:
 *   const city = createCityScene(hostElement, { quality: 'high' | 'low' });
 *   city.setProgress(0..1);   // trang chủ: camera hạ dần theo thao tác cuộn
 *   city.dispose();           // giải phóng toàn bộ tài nguyên GPU
 */

const PRESETS = {
    high: { blocks: 14, vehicles: 70, trail: 16, dpr: 1.75, pulses: 8, beacons: 4 },
    low:  { blocks: 9,  vehicles: 30, trail: 10, dpr: 1.25, pulses: 4, beacons: 3 },
};

const BLOCK = 10;          // một ô phố, tính cả đường
const STREET = 2.4;        // bề rộng đường
const TRAIL_STEP = 0.7;    // khoảng cách giữa hai điểm của vệt sáng
const TURN_CHANCE = 0.35;  // xác suất rẽ ở mỗi ngã tư
const CAR_Y = 0.6;

const COLOR = {
    ink: 0x05070a, ground: 0x070b0e, building: 0x0c1218, roof: 0x080c10,
    street: 0x1a2731, bone: 0xdfe7e0, teal: 0x86c6d2, vermilion: 0xe0231c,
};

const lerp = (a, b, t) => a + (b - a) * t;
const clamp01 = (v) => Math.min(1, Math.max(0, v));
/* làm mượt không phụ thuộc tốc độ khung hình */
const damp = (current, target, rate, dt) => lerp(current, target, 1 - Math.exp(-rate * dt));

/* số ngẫu nhiên có hạt giống: thành phố giống hệt nhau mỗi lần mở trang */
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

/* mặt tường nhà: nền đen, một phần cửa sổ sáng vàng ấm, vài cửa sổ ánh xanh */
function windowTexture(rnd) {
    return canvasTexture(64, 128, (ctx, w, h) => {
        ctx.fillStyle = '#000';
        ctx.fillRect(0, 0, w, h);
        const cols = 4, rows = 12, cw = w / cols, rh = h / rows;
        for (let r = 0; r < rows; r++) {
            for (let c = 0; c < cols; c++) {
                const v = rnd();
                if (v > 0.38) continue;
                ctx.globalAlpha = 0.35 + rnd() * 0.65;
                ctx.fillStyle = v < 0.08
                    ? 'rgb(150,215,228)'
                    : `rgb(255,${170 + ((v * 180) | 0)},${100 + ((v * 120) | 0)})`;
                ctx.fillRect(c * cw + cw * 0.22, r * rh + rh * 0.25, cw * 0.56, rh * 0.5);
            }
        }
        ctx.globalAlpha = 1;
    });
}

/* chấm sáng tròn, mềm ở rìa: đầu xe và quầng sáng cột cửa hàng */
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
    const noop = { setProgress() {}, dispose() {} };

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
    renderer.setClearColor(COLOR.ink, 1);

    const rnd = mulberry32(2026);
    const N = P.blocks;
    const half = (N * BLOCK) / 2;
    /* toạ độ các đường phố, giống nhau cho trục x và trục z */
    const S = Array.from({ length: N + 1 }, (_, k) => -half + k * BLOCK);

    const scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(COLOR.ink, 0.9 / (half * 2));
    const camera = new THREE.PerspectiveCamera(42, 1, 0.5, 600);
    const textures = [];

    /* ---------------------------------------------------------- ánh sáng */
    scene.add(new THREE.HemisphereLight(0x2c4250, COLOR.ink, 0.9));
    const moon = new THREE.DirectionalLight(0x9cc8d4, 0.45);
    moon.position.set(-40, 80, 30);
    scene.add(moon);

    /* ---------------------------------------------------------- mặt đất và đường */
    const ground = new THREE.Mesh(
        new THREE.PlaneGeometry(half * 8, half * 8),
        new THREE.MeshLambertMaterial({ color: COLOR.ground }),
    );
    ground.rotation.x = -Math.PI / 2;
    scene.add(ground);

    const streetLines = [];
    const far = half + BLOCK;
    S.forEach((s) => {
        [s - STREET / 2, s + STREET / 2].forEach((e) => {
            streetLines.push(e, 0.02, -far, e, 0.02, far);   // mép đường dọc
            streetLines.push(-far, 0.02, e, far, 0.02, e);   // mép đường ngang
        });
    });
    const streetGeo = new THREE.BufferGeometry();
    streetGeo.setAttribute('position', new THREE.Float32BufferAttribute(streetLines, 3));
    scene.add(new THREE.LineSegments(streetGeo, new THREE.LineBasicMaterial({ color: COLOR.street })));

    /* ---------------------------------------------------------- tòa nhà */
    const lots = [];
    for (let i = 0; i < N; i++) {
        for (let j = 0; j < N; j++) {
            const x0 = S[i] + STREET / 2, z0 = S[j] + STREET / 2, sub = (BLOCK - STREET) / 2;
            for (let a = 0; a < 2; a++) {
                for (let b = 0; b < 2; b++) {
                    if (rnd() < 0.15) continue;                     // bãi trống, công viên
                    const cx = x0 + sub * (a + 0.5), cz = z0 + sub * (b + 0.5);
                    const centre = 1 - Math.min(1, Math.hypot(cx, cz) / half);
                    const height = 1.5 + Math.pow(rnd(), 2.2) * (6 + 24 * centre); // cao dần về trung tâm
                    lots.push([cx, cz, sub * (0.62 + rnd() * 0.26), height, sub * (0.62 + rnd() * 0.26)]);
                }
            }
        }
    }

    const windows = windowTexture(rnd);
    textures.push(windows);
    const wall = new THREE.MeshLambertMaterial({
        color: COLOR.building, emissive: 0xffffff, emissiveMap: windows, emissiveIntensity: 0.75,
    });
    const roof = new THREE.MeshLambertMaterial({ color: COLOR.roof });
    /* thứ tự mặt của BoxGeometry: +x, -x, +y (mái), -y (đáy), +z, -z */
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
    const palette = [new THREE.Color(COLOR.bone), new THREE.Color(COLOR.teal), new THREE.Color(COLOR.vermilion)];
    const vehicles = [];
    for (let i = 0; i < P.vehicles; i++) {
        const kCur = (rnd() * (N + 1)) | 0;
        let dir = rnd() < 0.5 ? -1 : 1;
        if (kCur + dir < 0 || kCur + dir > N) dir = -dir;
        const rented = rnd() < 0.16;
        vehicles.push({
            axis: rnd() < 0.5 ? 0 : 1,           // 0: chạy theo trục z, 1: theo trục x
            fixedIdx: (rnd() * (N + 1)) | 0,     // đang ở đường số mấy
            kCur, dir, ni: kCur + dir,           // ngã tư hiện tại, ngã tư kế tiếp
            pos: S[kCur],
            speed: 4 + rnd() * 7,
            rented,
            color: rented ? palette[2] : palette[rnd() < 0.5 ? 0 : 1],
            trail: [],
        });
    }

    const worldOf = (v, out) => (v.axis === 0
        ? out.set(S[v.fixedIdx], CAR_Y, v.pos)
        : out.set(v.pos, CAR_Y, S[v.fixedIdx]));

    /* chạy một quãng đường; đi qua ngã tư nào thì quyết định rẽ hay đi thẳng ở đó */
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
            if (v.ni < 0 || v.ni > N) {        // hết đường ở rìa thành phố: quay đầu
                v.dir = -v.dir;
                v.ni = v.kCur + v.dir;
            }
        }
    }

    const glow = glowTexture();
    textures.push(glow);

    /* đầu xe: một đám điểm sáng */
    const headPos = new Float32Array(vehicles.length * 3);
    const headCol = new Float32Array(vehicles.length * 3);
    vehicles.forEach((v, i) => v.color.toArray(headCol, i * 3));
    const headGeo = new THREE.BufferGeometry();
    headGeo.setAttribute('position', new THREE.BufferAttribute(headPos, 3));
    headGeo.setAttribute('color', new THREE.BufferAttribute(headCol, 3));
    const heads = new THREE.Points(headGeo, new THREE.PointsMaterial({
        size: 2.2, map: glow, vertexColors: true, transparent: true,
        depthWrite: false, blending: THREE.AdditiveBlending,
    }));
    heads.frustumCulled = false;
    scene.add(heads);

    /* vệt sáng: mỗi xe T điểm lịch sử, nối thành T-1 đoạn, mờ dần về phía đuôi */
    const T = P.trail, SEGS = T - 1;
    const trailPos = new Float32Array(vehicles.length * SEGS * 6);
    const trailCol = new Float32Array(vehicles.length * SEGS * 6);
    const fade = (s) => Math.pow(1 - s / SEGS, 1.6) * 0.9;
    vehicles.forEach((v, i) => {
        let o = i * SEGS * 6;
        for (let s = 0; s < SEGS; s++) {
            for (const f of [fade(s), fade(s + 1)]) {
                trailCol[o++] = v.color.r * f;
                trailCol[o++] = v.color.g * f;
                trailCol[o++] = v.color.b * f;
            }
        }
    });
    const trailGeo = new THREE.BufferGeometry();
    trailGeo.setAttribute('position', new THREE.BufferAttribute(trailPos, 3));
    trailGeo.setAttribute('color', new THREE.BufferAttribute(trailCol, 3));
    const trails = new THREE.LineSegments(trailGeo, new THREE.LineBasicMaterial({
        vertexColors: true, transparent: true, depthWrite: false, blending: THREE.AdditiveBlending,
    }));
    trails.frustumCulled = false;
    scene.add(trails);

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
            color: COLOR.vermilion, transparent: true, opacity: 0,
            depthWrite: false, blending: THREE.AdditiveBlending,
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

    /* ---------------------------------------------------------- cột sáng cửa hàng */
    const beaconGeo = new THREE.CylinderGeometry(0.18, 0.18, 60, 10, 1, true).translate(0, 30, 0);
    const beacons = [];
    for (let b = 0; b < P.beacons; b++) {
        const x = S[1 + ((rnd() * (N - 1)) | 0)], z = S[1 + ((rnd() * (N - 1)) | 0)];
        const beam = new THREE.Mesh(beaconGeo, new THREE.MeshBasicMaterial({
            color: COLOR.vermilion, transparent: true, opacity: 0.28,
            depthWrite: false, blending: THREE.AdditiveBlending,
        }));
        beam.position.set(x, 0, z);
        const halo = new THREE.Sprite(new THREE.SpriteMaterial({
            map: glow, color: COLOR.vermilion, transparent: true,
            depthWrite: false, blending: THREE.AdditiveBlending,
        }));
        halo.position.set(x, 1, z);
        halo.scale.setScalar(7);
        scene.add(beam, halo);
        beacons.push({ beam, phase: rnd() * Math.PI * 2 });
    }

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
        if (!running) {                 // đứng yên (giảm chuyển động): vẽ lại ngay một khung
            cam.progress = cam.target;
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
            p.mesh.material.opacity = (1 - p.t) * 0.8;
        });

        beacons.forEach((b) => {
            b.beam.material.opacity = 0.22 + Math.sin(clock * 1.4 + b.phase) * 0.08;
        });
    }

    function render() {
        placeCamera();
        renderer.render(scene, camera);
    }

    function frame(now) {
        if (!running) return;
        const dt = Math.min((now - last) / 1000, 0.05);   // tab quay lại sau lâu cũng không nhảy cảnh
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

    /* driver đồ họa có thể thu hồi ngữ cảnh WebGL: chuyển về nền tĩnh thay vì để màn hình đen */
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
    vehicles.forEach(writeVehicle);
    resize();
    start();

    return { setProgress, dispose };
}