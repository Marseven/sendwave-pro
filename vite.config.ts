import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import path from "path";
import laravel from 'laravel-vite-plugin';

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => ({
  server: {
    host: "::",
    port: 8080,
  },
  plugins: [
    laravel({
      input: ['resources/src/main.ts'],
      refresh: true,
    }),
    vue(),
  ],
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./resources/src"),
    },
  },
}));
