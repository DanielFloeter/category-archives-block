import { defineConfig } from 'vitest/config';

export default defineConfig( {
	test: {
		// Let Vite transform the WordPress packages; Node cannot import their
		// JSON files without import attributes.
		server: { deps: { inline: [ /@wordpress\// ] } },
	},
} );
