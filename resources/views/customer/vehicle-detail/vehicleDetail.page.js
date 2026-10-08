import { renderStoreMap } from '../../../js/shared/map/storeMap';

const element = document.getElementById('store-map');
if (element) renderStoreMap(element, { zoom: 15 });