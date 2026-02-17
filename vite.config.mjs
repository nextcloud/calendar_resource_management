/**
 * SPDX-FileCopyrightText: 2026 Marcel Meyer <meyerm@strato.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
  plugins: [vue()],
  build: {
    outDir: 'js',
    emptyOutDir: true,
    rollupOptions: {
      external: ['@nextcloud/vue'],
      input: {
        'calendar_resource_management-adminSettings': resolve(__dirname, 'src/adminSettings.js'),
      },
      output: {
        entryFileNames: '[name].js',
        assetFileNames: '[name].[ext]',
        format: 'iife',
        globals: {
          '@nextcloud/vue': 'OCA.NextcloudVue',
        },
      }
    }
  }
});
