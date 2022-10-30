import { defineConfig } from 'vite';
import { resolve } from 'path'
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react'

const outDir = resolve(__dirname, 'public/resource')

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js', 'resources/js/main.tsx']
        }),
		react()
    ],
    base: '/resource/',
	build: {
        outDir,
        emptyOutDir: true,
        rollupOptions: {
            output: {
				entryFileNames: `[name].js`,   // works
				chunkFileNames: `chunk/[name].[hash].js`,   // works
				assetFileNames: `assets/[ext]/[hash][extname]`, // does not work for images
				manualChunks: {
					fetch: ["lodash", "axios"],
					vendor: ['react', 'react-dom', "react-router-dom"]
				}
			}
        }
    },
    server: {
		// required to load scripts from custom host
		cors: true,
	
		// we need a strict port to match on PHP side
		// change freely, but update on PHP to match the same port
		strictPort: true,
		port: 3000
	},
	resolve: {
		alias: [
		  // { find: '@components', replacement: resolve(__dirname, 'src/components') },
		//   { find: 'services', replacement: resolve(__dirname, 'resources/src/services') },
		//   { find: 'components', replacement: resolve(__dirname, 'resources/src/components') },
		//   { find: 'views', replacement: resolve(__dirname, 'resources/js/react/views') },
		],
	},
});
