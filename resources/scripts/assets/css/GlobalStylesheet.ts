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

    /* --- Premium Textured & Glassmorphic Custom Styling --- */
    
    body {
        background-color: #07080e !important;
        /* Technical grid texture overlay combined with glowing radial lights */
        background-image: 
            radial-gradient(circle at 50% 10%, rgba(139, 92, 246, 0.06) 0%, transparent 60%),
            radial-gradient(circle at 10% 80%, rgba(59, 130, 246, 0.03) 0%, transparent 50%),
            linear-gradient(rgba(255, 255, 255, 0.007) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.007) 1px, transparent 1px) !important;
        background-size: 100% 100%, 100% 100%, 32px 32px, 32px 32px !important;
        background-attachment: fixed !important;
    }

    /* Glossy 3D Reflection & Glowing Border for all Buttons */
    button, .btn, a[class*="Button"] {
        position: relative;
        overflow: hidden;
        background-image: linear-gradient(135deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0) 50%, rgba(0, 0, 0, 0.1) 50%, rgba(0, 0, 0, 0.25) 100%) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.15) !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        border-radius: 8px !important;
    }

    button:hover, .btn:hover, a[class*="Button"]:hover {
        box-shadow: 0 4px 20px rgba(139, 92, 246, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.25) !important;
        transform: translateY(-1px);
        filter: brightness(1.15);
    }
    
    button:active, .btn:active, a[class*="Button"]:active {
        transform: translateY(1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2) !important;
    }

    /* Frosted Glass Texture for Cards, Boxes, and Rows */
    div[class*="GreyRowBox"], 
    div[class*="ContentBox"], 
    div[class*="SupportContainer"] > div, 
    div[class*="LoginFormContainer"] > form > div {
        background-color: rgba(13, 14, 22, 0.8) !important;
        backdrop-filter: blur(16px) !important;
        -webkit-backdrop-filter: blur(16px) !important;
        border: 1px solid rgba(255, 255, 255, 0.03) !important;
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.45) !important;
        background-image: linear-gradient(180deg, rgba(255, 255, 255, 0.015) 0%, rgba(255, 255, 255, 0) 100%) !important;
        border-radius: 12px !important;
    }

    /* Sleek Glow & Transparent Look for Inputs */
    input, select, textarea {
        background-color: rgba(13, 14, 22, 0.6) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        border-radius: 8px !important;
        transition: all 0.2s ease !important;
    }

    input:focus, select:focus, textarea:focus {
        border-color: rgba(139, 92, 246, 0.5) !important;
        box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.15) !important;
        background-color: rgba(13, 14, 22, 0.85) !important;
    }
`;

