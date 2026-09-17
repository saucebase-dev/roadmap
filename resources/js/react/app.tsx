import { registerIcon } from '@/lib/navigation';

import '@modules/roadmap/resources/css/style.css';

import IconMap from '~icons/heroicons/map';

export function setup() {
    registerIcon('roadmap', IconMap);
}

export function afterMount() {}
