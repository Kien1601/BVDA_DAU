import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css',
                    'resources/js/app.js',
                    'resources/views/admin/gps-monitor/gpsMonitor.page.js',
                    'resources/views/admin/store-management/storeManagement.form.js',
                    'resources/views/admin/vehicle-management/vehicleManagement.form.js',
                    'resources/views/admin/promotion/promotion.page.js',
                    'resources/views/layouts/guest.page.js',
                ],
            refresh: true,
        }),
    ],
});
