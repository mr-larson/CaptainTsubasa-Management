/* Lint du front (Vue 3 + JS). Le formatage est confié à Prettier :
   eslint-config-prettier désactive les règles de mise en forme. */
module.exports = {
    root: true,
    env: { browser: true, es2022: true, node: true },
    parserOptions: { ecmaVersion: 'latest', sourceType: 'module' },
    extends: ['eslint:recommended', 'plugin:vue/vue3-recommended', 'prettier'],
    globals: {
        route: 'readonly', // Ziggy (@routes)
        Ziggy: 'readonly',
        axios: 'readonly', // window.axios (bootstrap.js)
    },
    rules: {
        // Règles de style Vue désactivées : le code existant ne les suit pas et
        // le formatage relève de Prettier.
        'vue/multi-word-component-names': 'off',
        'vue/require-default-prop': 'off',
        'vue/attributes-order': 'off',
        'vue/no-v-html': 'off',
        'vue/first-attribute-linebreak': 'off',
        'vue/attribute-hyphenation': 'off',      // props en camelCase dans tout le projet
        'vue/v-on-event-hyphenation': 'off',
        // Les composants H1/H2 existent depuis le début ; Vue les distingue de <h1>/<h2>.
        'vue/no-reserved-component-names': 'off',
        // Un formulaire useForm passé en prop est modifié champ par champ (form.x) : autorisé.
        'vue/no-mutating-props': ['error', { shallowOnly: true }],
        'no-unused-vars': ['warn', { argsIgnorePattern: '^_', varsIgnorePattern: '^_' }],
    },
    ignorePatterns: ['public/', 'vendor/', 'node_modules/', 'storage/'],
};
