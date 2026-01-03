module.exports = {
    content: [
        './resources/views/**/*.blade.php',
        './app/Filament/**/*.php',
        './resources/js/**/*.js',
        './resources/css/**/*.css'
    ],
    safelist: [
        'fi-header',
        'fi-main',
        'fi-main-text',
        'fi-btn',
        'fi-btn-primary',
        'fi-btn-secondary',
        'fi-btn-success',
        'fi-btn-danger',
        'fi-badge',
        'fi-badge-primary',
        'fi-badge-success',
        'fi-badge-warning',
        'fi-badge-danger',
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
        'fi-topbar',
        'fi-header-bar',
        'fi-user-menu',
        'fi-user-menu-panel'
        // ...add more if needed...
    ],
    theme: {
        extend: {}
    },
    plugins: []
};
