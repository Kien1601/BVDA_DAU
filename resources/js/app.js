import './shared/config/echo';
import { initThemeToggle } from './shared/theme/theme';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

initThemeToggle();
