module.exports = {

    markdown: {
        anchor: { level: [2, 3] },
        extendMarkdown(md) {
            let markup = require("vuepress-theme-craftdocs/markup");
            md.use(markup);
        },
    },

    base: '/mapbox/',
    title: 'Mapbox plugin for Craft CMS',
    plugins: [
        [
            'vuepress-plugin-clean-urls',
            {
                normalSuffix: '/',
                indexSuffix: '/',
                notFoundPath: '/404.html',
            },
        ],
    ],
    theme: "craftdocs",
    themeConfig: {
        codeLanguages: {
            php: "PHP",
            twig: "Twig",
            html: "HTML",
            js: "JavaScript",
        },
        logo: '/images/icon.svg',
        searchMaxSuggestions: 10,
        nav: [
            {text: 'Getting Started', link: '/getting-started/'},
            {
                text: 'How It Works',
                items: [
                    {text: 'Address Field', link: '/address-field/'},
                    {text: 'Dynamic Maps', link: '/dynamic-maps/'},
                ]
            },
            {
                text: 'Architecture',
                items: [
                    {text: 'JavaScript', link: '/javascript/'},
                    {text: 'Twig/PHP', link: '/helper/'},
                    {text: 'Models', link: '/models/'},
                ]
            },
            {
                text: 'Guides',
                items: [
                    { text: 'Address Field', items: [
                        {text: 'Address in a Matrix Field', link: '/guides/address-in-a-matrix-field/'},
                    ] },
                    { text: 'Dynamic Maps', items: [
                        {text: 'Troubleshoot Dynamic Maps', link: '/guides/troubleshoot-dynamic-maps/'},
                        {text: 'Delaying Map Initialization', link: '/guides/delay-map-init/'},
                        {text: 'Required JS Assets', link: '/guides/required-js-assets/'},
                        {text: 'Setting the Map Height', link: '/guides/setting-map-height/'},
                        {text: 'Styling a Map', link: '/guides/styling-a-map/'},
                        {text: 'Setting Marker Icons', link: '/guides/setting-marker-icons/'},
                        {text: 'Opening Popups', link: '/guides/opening-popups/'},
                        {text: 'Changing the Map Language', link: '/guides/changing-map-language/'},
                        {text: 'Prevent Zoom When Scrolling', link: '/guides/prevent-zoom-when-scrolling/'},
                        {text: 'Bermuda Triangle', link: '/guides/bermuda-triangle/'},
                    ] },
                ]
            },
        ],
        sidebar: {
            // Getting Started
            '/getting-started/': [
                '',
                'access-token',
                'settings',
                'config',
            ],

            // How It Works
            '/address-field/': [
                '',
                'how-it-works',
                'settings',
                'twig',
            ],
            '/dynamic-maps/': [
                {
                    title: 'Overview',
                    collapsable: false,
                    children: [
                        '',
                        'universal-api',
                        'chaining',
                        'locations',
                    ]
                },
                {
                    title: 'Map Management',
                    collapsable: false,
                    children: [
                        'basic-map-management',
                        'universal-methods',
                        'javascript-methods',
                        'twig-php-methods',
                    ]
                },
                {
                    title: 'More Features',
                    collapsable: false,
                    children: [
                        'popups',
                        'troubleshooting',
                    ]
                },
            ],

            // Architecture
            '/helper/': [
                '',
                'dynamic-maps',
                'api',
            ],
            '/javascript/': [
                '',
                'mapbox.js',
                'dynamicmap.js',
            ],
            '/models/': [
                '',
                'address-model',
                'location-model',
                'dynamic-map-model',
                'settings-model',
                'coordinates',
            ],

            // Guides
            '/guides/': [
                {
                    title: 'Address Field',
                    collapsable: false,
                    children: [
                        'address-in-a-matrix-field',
                    ]
                },
                {
                    title: 'Dynamic Maps',
                    collapsable: false,
                    children: [
                        'troubleshoot-dynamic-maps',
                        'delay-map-init',
                        'required-js-assets',
                        'setting-map-height',
                        'styling-a-map',
                        'setting-marker-icons',
                        'opening-popups',
                        'changing-map-language',
                        'prevent-zoom-when-scrolling',
                        'bermuda-triangle',
                    ]
                },
            ],

        }
    }
};
