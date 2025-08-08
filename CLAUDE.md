# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is an Astro-based website for Palma Miami Beach, a luxury residential development. The site is built using Astro 5.12.8 with SCSS for styling and includes multiple pages showcasing amenities, residences, gallery, neighborhood, and team information.

## Development Commands

All commands should be run from the project root:

- `npm run dev` - Start development server at localhost:4321
- `npm run build` - Build production site to ./dist/
- `npm run preview` - Preview production build locally
- `npm run astro` - Run Astro CLI commands

## Architecture

### Core Structure
- **Layout System**: Single main layout (`src/layouts/Layout.astro`) with comprehensive SEO meta tags, structured data, and social media integration
- **Component Architecture**: Modular Astro components in `src/components/` including Header, Footer, Hero, and LanguageSwitcher
- **Page Structure**: Static pages in `src/pages/` for each main section (index, amenities, gallery, neighborhood, residences, team)
- **Asset Management**: Images organized by section in `public/images/` with custom fonts in `public/fonts/`

### Key Features
- **SEO Optimization**: Comprehensive meta tags, Open Graph, Twitter Cards, and JSON-LD structured data
- **Typography**: Custom Didot font family with multiple weights and styles
- **Image Organization**: Webp format images organized by page sections
- **SCSS Styling**: Minimal reset styles in `src/styles/main.scss`

### Site Configuration
- Domain: palmamiamibeach.com
- Base Astro config with minimal setup
- TypeScript support enabled via tsconfig.json

## Content Structure

The site features a luxury real estate focus with organized visual assets:
- Home page hero and lifestyle imagery
- Amenities showcase (pool, fitness, aqua lounge)
- Gallery thumbnails and full images
- Neighborhood lifestyle photography
- Team and project branding elements

## Development Notes

- The project uses ES modules (`"type": "module"` in package.json)
- SCSS compilation is handled by Astro's built-in support
- No testing framework or linting tools are currently configured
- JavaScript functionality is minimal with basic imports in `src/scripts/main.js`