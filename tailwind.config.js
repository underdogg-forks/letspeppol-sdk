module.exports = {
    content: [
        './resources/views/**/*.blade.php',
        './app/Filament/**/*.php',
        './resources/js/**/*.js',
        './resources/css/**/*.css'
    ],
    safelist: [
        'fi-header',
        'fi-header-bar',
        'fi-page-title',
        'fi-main',
        'fi-main-text',
        'fi-btn',
        'fi-btn-primary',
        'fi-btn-secondary',
        'fi-btn-success',
        'fi-btn-danger',
        'fi-badge',
        'fi-badges-row',
        'fi-badge-primary',
        'fi-badge-success',
        'fi-badge-warning',
        'fi-badge-danger',
        'fi-badge-active',
        'fi-alert',
        'fi-alert-info',
        'fi-alert-success',
        'fi-alert-warning',
        'fi-alert-danger',
        'fi-input',
        'fi-checkbox',
        'fi-radio',
        'fi-table',
        'fi-table-header',
        'fi-table-cell',
        'fi-table-row-odd',
        'fi-table-row-even',
        'fi-card',
        'fi-icon-primary',
        'fi-icon-success',
        'fi-icon-warning',
        'fi-icon-danger',
        'fi-icon-secondary',
        'fi-sidebar',
        'fi-sidebar-panel',
        'fi-sidebar-content',
        'fi-sidebar-header',
        'fi-sidebar-footer',
        'fi-topbar',
        'fi-user-menu',
        'fi-user-menu-panel',
        // Patterns for nested/variant classes:
        { pattern: /fi-main-text/ }, // .fi-main-text, .fi-main-text ul li, etc.
        { pattern: /fi-badge/ },     // .fi-badge, .fi-badge-success, etc.
        { pattern: /fi-badges-row/ },
        { pattern: /fi-table-header/ },
        { pattern: /fi-card/ },      // .fi-card, .fi-card .fi-header, etc.
        { pattern: /fi-header/ }    // .fi-header, h1.fi-header, etc.
    ],
    theme: {
        extend: {}
    },
    plugins: []
};
