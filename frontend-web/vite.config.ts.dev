import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

import path from 'path';

export default defineConfig({
  plugins: [react()],
  base: '/',
  server: {
    proxy: {
      '/api': 'http://localhost:8787',
      '/sanctum': 'http://localhost:8787',
    },
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src') // Direct path to src directory for the alias
    },
  },
  build: {
    chunkSizeWarningLimit: 1500, // Increase limit if needed
    rollupOptions: {
      output: {
        manualChunks: {
          react: ['react', 'react-dom'],
          handsontable: ['handsontable', '@handsontable/react'],
        },
      },
    },
  },
});
