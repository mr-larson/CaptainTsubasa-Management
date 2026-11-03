import { defineConfig } from 'vitest/config';

// Tests unitaires du moteur de match (modules JS purs, sans navigateur).
// Config séparée de vite.config.js : le plugin Laravel n'est pas utile ici.
export default defineConfig({
    test: {
        include: ['tests/js/**/*.test.js'],
        environment: 'node',
    },
});
