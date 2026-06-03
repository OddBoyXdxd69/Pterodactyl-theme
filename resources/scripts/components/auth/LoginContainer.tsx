import React, { useEffect, useRef, useState } from 'react';
import { Link, RouteComponentProps } from 'react-router-dom';
import login from '@/api/auth/login';
import LoginFormContainer from '@/components/auth/LoginFormContainer';
import { useStoreState } from 'easy-peasy';
import { Formik, FormikHelpers } from 'formik';
import { object, string } from 'yup';
import Field from '@/components/elements/Field';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import Captcha, { CaptchaRef } from '@/components/elements/Captcha';
import useFlash from '@/plugins/useFlash';

interface Values {
    username: string;
    password: string;
}

const LoginContainer = ({ history }: RouteComponentProps) => {
    const ref = useRef<CaptchaRef>(null);
    const [token, setToken] = useState('');

    const { addFlash, clearFlashes, clearAndAddHttpError } = useFlash();
    const { enabled: recaptchaEnabled, siteKey } = useStoreState((state) => state.settings.data!.recaptcha);
    const name = useStoreState((state) => state.settings.data?.name || 'Pterodactyl');
    const registrationEnabled = useStoreState((state) => state.settings.data?.registration?.enabled ?? false);
    const discordEnabled = useStoreState((state) => state.settings.data?.discord?.enabled ?? false);

    useEffect(() => {
        clearFlashes();

        const params = new URLSearchParams(window.location.search);
        const error = params.get('error');
        if (error) {
            let message = 'An error occurred during Discord authentication.';
            if (error === 'discord_disabled') {
                message = 'Discord authentication is currently disabled.';
            } else if (error === 'discord_not_configured') {
                message = 'Discord authentication is not fully configured by administrator.';
            } else if (error === 'discord_code_missing') {
                message = 'Authorization code is missing from Discord redirect.';
            } else if (error === 'discord_invalid_state') {
                message = 'Invalid session state. Please try logging in again.';
            } else if (error === 'discord_token_failed' || error === 'discord_exchange_error') {
                message = 'Failed to exchange authorization token with Discord.';
            } else if (error === 'discord_email_missing') {
                message = 'Your Discord account does not have an email address associated with it.';
            } else if (error === 'discord_email_unverified') {
                message = 'Please verify your email address on Discord before trying to log in.';
            } else if (error === 'discord_registration_disabled') {
                message = 'You do not have an account, and self-service registration is disabled.';
            } else if (error === 'discord_profile_error') {
                message = 'Failed to retrieve your Discord profile details.';
            } else if (error === 'discord_creation_error') {
                message = 'An error occurred while creating your account. Please contact support.';
            }
            addFlash({
                type: 'error',
                title: 'Discord Login Failed',
                message,
            });
            history.replace(window.location.pathname);
        }
    }, []);

    const onSubmit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes();

        // If there is no token in the state yet, request the token and then abort this submit request
        // since it will be re-submitted when the recaptcha data is returned by the component.
        if (recaptchaEnabled && !token) {
            ref.current!.execute().catch((error) => {
                console.error(error);

                setSubmitting(false);
                clearAndAddHttpError({ error });
            });

            return;
        }

        login({ ...values, recaptchaData: token })
            .then((response) => {
                if (response.complete) {
                    // @ts-expect-error this is valid
                    window.location = response.intended || '/';
                    return;
                }

                history.replace('/auth/login/checkpoint', { token: response.confirmationToken });
            })
            .catch((error) => {
                console.error(error);

                setToken('');
                if (ref.current) ref.current.reset();

                setSubmitting(false);
                clearAndAddHttpError({ error });
            });
    };

    return (
        <Formik
            onSubmit={onSubmit}
            initialValues={{ username: '', password: '' }}
            validationSchema={object().shape({
                username: string().required('A username or email must be provided.'),
                password: string().required('Please enter your account password.'),
            })}
        >
            {({ isSubmitting, setSubmitting, submitForm }) => (
                <LoginFormContainer title={'Login to Continue'} css={tw`w-full flex`}>
                    <Field type={'text'} label={'Username or Email'} name={'username'} disabled={isSubmitting} />
                    <div css={tw`mt-6`}>
                        <Field type={'password'} label={'Password'} name={'password'} disabled={isSubmitting} />
                    </div>
                    <div css={tw`mt-6`}>
                        <Button 
                            type={'submit'} 
                            size={'xlarge'} 
                            isLoading={isSubmitting} 
                            disabled={isSubmitting}
                            css={tw`bg-purple-600 hover:bg-purple-700 border-purple-700 hover:border-purple-800 text-white font-bold tracking-wide rounded-xl shadow-lg transition-all transform active:scale-95`}
                        >
                            Login
                        </Button>
                    </div>
                    <Captcha
                        ref={ref}
                        onVerify={(response) => {
                            setToken(response);
                            submitForm();
                        }}
                        onExpire={() => {
                            setSubmitting(false);
                            setToken('');
                        }}
                    />
                    <div css={tw`mt-6 text-center`}>
                        <Link
                            to={'/auth/password'}
                            css={tw`text-xs text-neutral-400 tracking-wide no-underline uppercase hover:text-purple-400 transition-colors`}
                        >
                            Forgot password?
                        </Link>
                    </div>
                    {discordEnabled && (
                        <>
                            <div css={tw`flex items-center my-6`}>
                                <div css={tw`flex-1 h-px bg-neutral-700`} />
                                <span css={tw`px-3 text-xs text-neutral-500 uppercase tracking-wider`}>or</span>
                                <div css={tw`flex-1 h-px bg-neutral-700`} />
                            </div>
                            <a
                                href={'/auth/discord'}
                                style={{ backgroundColor: '#5865F2' }}
                                css={tw`flex items-center justify-center w-full py-3 px-4 rounded-xl text-sm font-semibold text-white hover:bg-opacity-90 active:bg-opacity-80 shadow-lg hover:shadow-xl transition-all transform active:scale-95 text-center no-underline select-none`}
                            >
                                <svg style={{ width: '20px', height: '20px', fill: 'currentColor', marginRight: '8px' }} viewBox="0 0 127.14 96.36">
                                    <path d="M107.7,8.07A105.15,105.15,0,0,0,77.26,0a77.19,77.19,0,0,0-3.3,6.83A96.67,96.67,0,0,0,53.22,6.83,77.19,77.19,0,0,0,49.88,0,105.15,105.15,0,0,0,19.44,8.07C3.66,31.58-1.86,54.65,1,77.53A105.73,105.73,0,0,0,32,96.36a77.7,77.7,0,0,0,6.63-10.85,68.43,68.43,0,0,1-10.5-5c.88-.65,1.72-1.34,2.51-2a75.58,75.58,0,0,0,73,0c.79.71,1.63,1.4,2.51,2a68.43,68.43,0,0,1-10.5,5,77.7,77.7,0,0,0,6.63,10.85,105.73,105.73,0,0,0,31-18.83C129.07,48.51,122.3,25.68,107.7,8.07ZM42.45,65.69C36.18,65.69,31,60,31,53S36.18,40.36,42.45,40.36,53.83,46,53.83,53,48.72,65.69,42.45,65.69Zm42.24,0C78.41,65.69,73.24,60,73.24,53S78.41,40.36,84.69,40.36,96.07,46,96.07,53,91,65.69,84.69,65.69Z"/>
                                </svg>
                                Login with Discord
                            </a>
                        </>
                    )}
                    {registrationEnabled && (
                        <div css={tw`mt-6 text-center text-xs text-neutral-500`}>
                            New to {name}?{' '}
                            <Link
                                to={'/auth/register'}
                                css={tw`text-purple-400 hover:text-purple-300 font-semibold no-underline transition-colors`}
                            >
                                Register
                            </Link>
                        </div>
                    )}
                </LoginFormContainer>
            )}
        </Formik>
    );
};

export default LoginContainer;
