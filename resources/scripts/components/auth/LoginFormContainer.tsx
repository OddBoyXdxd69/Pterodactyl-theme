import React, { forwardRef } from 'react';
import { Form } from 'formik';
import styled from 'styled-components/macro';
import { breakpoint } from '@/theme';
import FlashMessageRender from '@/components/FlashMessageRender';
import tw from 'twin.macro';
import { useStoreState } from 'easy-peasy';

type Props = React.DetailedHTMLProps<React.FormHTMLAttributes<HTMLFormElement>, HTMLFormElement> & {
    title?: string;
};

const Container = styled.div`
    ${tw`w-full max-w-full px-4`};
    box-sizing: border-box;

    ${breakpoint('sm')`
        ${tw`w-4/5 mx-auto px-0`}
    `};

    ${breakpoint('md')`
        ${tw`p-6`}
    `};

    ${breakpoint('lg')`
        ${tw`w-3/5`}
    `};

    ${breakpoint('xl')`
        ${tw`w-full`}
        max-width: 580px;
    `};
`;

export default forwardRef<HTMLFormElement, Props>(({ title, ...props }, ref) => {
    const logo = useStoreState((state) => state.settings.data?.theme?.logo) || '/assets/svgs/pterodactyl.svg';
    const footer = useStoreState((state) => state.settings.data?.theme?.footer);

    return (
        <Container>
            {title && <h2 css={tw`text-3xl text-center text-white font-semibold py-4 tracking-tight`}>{title}</h2>}
            <FlashMessageRender css={tw`mb-2 px-1`} />
            <Form {...props} ref={ref}>
                <div css={tw`flex flex-col md:flex-row w-full bg-[#0d0e16] bg-opacity-80 border border-neutral-800 shadow-2xl rounded-2xl p-6`} style={{ boxSizing: 'border-box' }}>
                    <div css={tw`flex-none select-none mb-6 md:mb-0 self-center`}>
                        <img 
                            src={logo} 
                            css={tw`block w-24 md:w-40 mx-auto`}
                            style={{ filter: 'drop-shadow(0 0 20px rgba(139, 92, 246, 0.35))' }}
                        />
                    </div>
                    <div css={tw`flex-1 md:pl-6 min-w-0`}>{props.children}</div>
                </div>
            </Form>
            <p css={tw`text-center text-neutral-500 text-xs mt-6`}>
                {footer ? (
                    footer
                ) : (
                    <>
                        &copy; 2015 - {new Date().getFullYear()}&nbsp;
                        <a
                            rel={'noopener nofollow noreferrer'}
                            href={'https://pterodactyl.io'}
                            target={'_blank'}
                            css={tw`no-underline text-neutral-500 hover:text-purple-400 transition-colors`}
                        >
                            Pterodactyl Software
                        </a>
                    </>
                )}
            </p>
        </Container>
    );
});
