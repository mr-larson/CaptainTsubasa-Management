import { defineConfig } from 'vitest/config';
import { fileURLToPath } from 'node:url';

// Tests unitaires du moteur de match (modules JS purs, sans navigateur).
// Config séparée de vite.config.js : le plugin Laravel n'est pas utile ici.
export default defineConfig({
    resolve: { alias: { '@': fileURLToPath(new URL('./resources/js', import.meta.url)) } },
    test: {
        include: ['tests/js/**/*.test.js'],
        environment: 'node',
    },
});
