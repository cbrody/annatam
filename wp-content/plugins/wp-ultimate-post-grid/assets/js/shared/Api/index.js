const { hooks } = WPUltimatePostGrid.shared;

import General from './General';
import Grid from './Grid';
import Manage from './Manage';
import Preview from './Preview';

const api = hooks.applyFilters( 'api', {
    general: General,
    grid: Grid,
    manage: Manage,
    preview: Preview,
} );

export default api;