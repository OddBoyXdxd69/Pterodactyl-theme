import tw from 'twin.macro';
import { createGlobalStyle } from 'styled-components/macro';
// @ts-expect-error untyped font file
import font from '@fontsource-variable/ibm-plex-sans/files/ibm-plex-sans-latin-wght-normal.woff2';

export default createGlobalStyle`
    @font-face {
        font-family: 'IBM Plex Sans';
        font-style: normal;
        font-display: swap;
        font-weight: 100 700;
        src: url(${font}) format('woff2-variations');
        unicode-range: U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD;
    }

    body {
        ${tw`font-sans bg-neutral-900 text-neutral-200`};
        letter-spacing: 0.015em;
    }

    h1, h2, h3, h4, h5, h6 {
        ${tw`font-medium tracking-normal font-header`};
    }

    p {
        ${tw`text-neutral-200 leading-snug font-sans`};
    }

    form {
        ${tw`m-0`};
    }

    textarea, select, input, button, button:focus, button:focus-visible {
        ${tw`outline-none`};
    }

    input[type=number]::-webkit-outer-spin-button,
    input[type=number]::-webkit-inner-spin-button {
        -webkit-appearance: none !important;
        margin: 0;
    }

    input[type=number] {
        -moz-appearance: textfield !important;
    }

    /* Scroll Bar Style */
    ::-webkit-scrollbar {
        background: none;
        width: 16px;
        height: 16px;
    }

    ::-webkit-scrollbar-thumb {
        border: solid 0 rgb(0 0 0 / 0%);
        border-right-width: 4px;
        border-left-width: 4px;
        -webkit-border-radius: 9px 4px;
        -webkit-box-shadow: inset 0 0 0 1px hsl(211, 10%, 53%), inset 0 0 0 4px hsl(209deg 18% 30%);
    }

    ::-webkit-scrollbar-track-piece {
        margin: 4px 0;
    }

    ::-webkit-scrollbar-thumb:horizontal {
        border-right-width: 0;
        border-left-width: 0;
        border-top-width: 4px;
        border-bottom-width: 4px;
        -webkit-border-radius: 4px 9px;
    }

    ::-webkit-scrollbar-corner {
        background: transparent;
    }

    :root {
        --app-bg: #07080e;
        --card-bg: #11121c;
        --sidebar-bg: #0b0c16;
        --text-main: #e5e7eb;
        --text-muted: #9ca3af;
        --border-color: #1b1c26;
    }
    
    :root.light-mode, body.light-mode {
        --app-bg: #f9fafb;
        --card-bg: #ffffff;
        --sidebar-bg: #f3f4f6;
        --text-main: #1f2937;
        --text-muted: #4b5563;
        --border-color: #e5e7eb;
    }

    body.light-mode {
        background-color: var(--app-bg) !important;
        color: var(--text-main) !important;
    }
    
    /* Text Color Overrides for Light Mode */
    body.light-mode p:not(.theme-sidebar *):not(.alert *):not([class*="Alert"] *),
    body.light-mode h1:not(.theme-sidebar *),
    body.light-mode h2:not(.theme-sidebar *),
    body.light-mode h3:not(.theme-sidebar *),
    body.light-mode h4:not(.theme-sidebar *),
    body.light-mode h5:not(.theme-sidebar *),
    body.light-mode h6:not(.theme-sidebar *),
    body.light-mode label:not(.theme-sidebar *),
    body.light-mode span:not(.theme-sidebar *):not(button *):not([class*="ButtonStyle"] *):not(.btn *):not([class*="Badge"] *):not(.badge *):not([class*="status"] *):not([class*="status-"] *):not(.alert *):not([class*="Alert"] *),
    body.light-mode a:not(.btn):not(.sidebar-link-text):not(.theme-sidebar *):not(button *):not([class*="ButtonStyle"] *):not([class*="SubNavigation"] > div > a) {
        color: var(--text-main) !important;
    }

    /* Topbar Styles in Light Mode */
    body.light-mode .light-topbar {
        background-color: #ffffff !important;
        border-color: #e5e7eb !important;
    }
    body.light-mode .light-topbar a,
    body.light-mode .light-topbar button,
    body.light-mode .light-topbar svg {
        color: #4b5563 !important;
    }
    body.light-mode .light-topbar a:hover,
    body.light-mode .light-topbar button:hover {
        color: #8b5cf6 !important;
        background-color: #f3f4f6 !important;
    }
    body.light-mode .light-topbar .bg-neutral-900 {
        background-color: #f3f4f6 !important;
        border-color: #e5e7eb !important;
    }
    
    /* Background Overrides for Light Mode (Excluding theme-sidebar) */
    body.light-mode .bg-neutral-900:not(.theme-sidebar *),
    body.light-mode .bg-\[\#07080e\]:not(.theme-sidebar):not(.theme-sidebar *),
    body.light-mode .bg-\[\#0d0e16\]:not(.theme-sidebar *) {
        background-color: var(--app-bg) !important;
    }
    
    body.light-mode .bg-neutral-800:not(.theme-sidebar *),
    body.light-mode .bg-\[\#0b0c16\]:not(.theme-sidebar):not(.theme-sidebar *) {
        background-color: var(--card-bg) !important;
    }
    
    body.light-mode .bg-neutral-700:not(.theme-sidebar *),
    body.light-mode .bg-\[\#11121c\]:not(.theme-sidebar *) {
        background-color: var(--card-bg) !important;
    }

    body.light-mode .bg-neutral-600:not(.theme-sidebar *) {
        background-color: #f3f4f6 !important;
    }

    /* Border Overrides for Light Mode */
    body.light-mode .border-neutral-800:not(.theme-sidebar *),
    body.light-mode .border-neutral-700:not(.theme-sidebar *),
    body.light-mode .border-neutral-600:not(.theme-sidebar *) {
        border-color: var(--border-color) !important;
    }

    /* Text Helper Classes Overrides for Light Mode (Excluding theme-sidebar) */
    body.light-mode .text-neutral-400:not(.theme-sidebar *):not(button *):not([class*="ButtonStyle"] *):not(.btn *),
    body.light-mode .text-neutral-500:not(.theme-sidebar *):not(button *):not([class*="ButtonStyle"] *):not(.btn *) {
        color: var(--text-muted) !important;
    }

    body.light-mode .text-neutral-300:not(.theme-sidebar *):not(button *):not([class*="ButtonStyle"] *):not(.btn *),
    body.light-mode .text-neutral-200:not(.theme-sidebar *):not(button *):not([class*="ButtonStyle"] *):not(.btn *),
    body.light-mode .text-neutral-100:not(.theme-sidebar *):not(button *):not([class*="ButtonStyle"] *):not(.btn *) {
        color: var(--text-main) !important;
    }

    /* Hover State Overrides for Light Mode */
    body.light-mode .hover\:bg-neutral-800:hover:not(.theme-sidebar *),
    body.light-mode .hover\:bg-neutral-800\/50:hover:not(.theme-sidebar *) {
        background-color: rgba(0, 0, 0, 0.03) !important;
    }

    /* Keep Terminal and Code Editors Dark in Light Mode */
    body.light-mode .terminal,
    body.light-mode .xterm-rows,
    body.light-mode .CodeMirror,
    body.light-mode .bg-black {
        background-color: #000000 !important;
        color: #ffffff !important;
    }
    body.light-mode .terminal span,
    body.light-mode .xterm-rows span,
    body.light-mode .CodeMirror span {
        color: inherit !important;
    }

    /* Explicit Reset for theme-sidebar to Remain Perfectly Dark */
    body.light-mode .theme-sidebar {
        background-color: #0b0c16 !important;
        border-color: #1b1c26 !important;
    }
    body.light-mode .theme-sidebar div {
        border-color: #1b1c26 !important;
    }
    body.light-mode .theme-sidebar .bg-\[\#07080e\] {
        background-color: #07080e !important;
    }
    body.light-mode .theme-sidebar a,
    body.light-mode .theme-sidebar button,
    body.light-mode .theme-sidebar span,
    body.light-mode .theme-sidebar svg {
        color: #9ca3af !important;
    }
    body.light-mode .theme-sidebar a:hover,
    body.light-mode .theme-sidebar button:hover,
    body.light-mode .theme-sidebar a:hover span,
    body.light-mode .theme-sidebar button:hover span,
    body.light-mode .theme-sidebar a:hover svg,
    body.light-mode .theme-sidebar button:hover svg {
        color: #ffffff !important;
        background-color: rgba(255, 255, 255, 0.05) !important;
    }
    body.light-mode .theme-sidebar a.active,
    body.light-mode .theme-sidebar a.active span,
    body.light-mode .theme-sidebar a.active svg {
        color: #ffffff !important;
        background-color: rgba(0, 0, 0, 0.25) !important;
        border-left-color: #8b5cf6 !important;
    }
    body.light-mode .theme-sidebar .font-bold {
        color: #ffffff !important;
    }

    /* Form Fields Styling in Light Mode */
    body.light-mode input:not([type="checkbox"]):not([type="radio"]),
    body.light-mode textarea,
    body.light-mode select {
        background-color: #f3f4f6 !important;
        border-color: #e5e7eb !important;
        color: #1f2937 !important;
    }
    body.light-mode input:not([type="checkbox"]):not([type="radio"]):hover,
    body.light-mode textarea:hover,
    body.light-mode select:hover {
        border-color: #d1d5db !important;
    }
    body.light-mode input:not([type="checkbox"]):not([type="radio"]):focus,
    body.light-mode textarea:focus,
    body.light-mode select:focus {
        background-color: #ffffff !important;
        border-color: #8b5cf6 !important;
    }

    /* SubNavigation Tabs in Light Mode */
    body.light-mode [class*="SubNavigation"] > div > a {
        color: var(--text-muted) !important;
    }
    body.light-mode [class*="SubNavigation"] > div > a:hover {
        color: var(--text-main) !important;
        background-color: rgba(0, 0, 0, 0.02) !important;
    }
    body.light-mode [class*="SubNavigation"] > div > a.active {
        color: #8b5cf6 !important;
        box-shadow: inset 0 -2px #8b5cf6 !important;
        background-color: transparent !important;
    }
`;
