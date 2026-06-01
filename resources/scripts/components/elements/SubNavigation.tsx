import styled from 'styled-components/macro';
import tw, { theme } from 'twin.macro';

const SubNavigation = styled.div`
    ${tw`w-full bg-neutral-800 shadow overflow-x-auto border-b border-neutral-700`};

    scrollbar-width: none !important;
    -ms-overflow-style: none !important;

    &::-webkit-scrollbar {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
        background: transparent !important;
    }
    &::-webkit-scrollbar-thumb {
        display: none !important;
        background: transparent !important;
    }
    &::-webkit-scrollbar-track {
        display: none !important;
        background: transparent !important;
    }

    & > div {
        ${tw`flex items-center text-sm mx-auto px-2`};
        max-width: 1200px;

        & > a,
        & > div {
            ${tw`inline-block py-3 px-4 text-neutral-300 no-underline whitespace-nowrap transition-all duration-150`};

            &:not(:first-of-type) {
                ${tw`ml-2`};
            }

            &:hover {
                ${tw`text-neutral-100`};
            }

            &:active,
            &.active {
                ${tw`text-neutral-100`};
                box-shadow: inset 0 -2px ${theme`colors.primary.500`.toString()};
            }
        }
    }
`;

export default SubNavigation;
