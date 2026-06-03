import React, { useRef, useState, useEffect } from 'react';
import { Link, RouteComponentProps } from 'react-router-dom';
import register, { verifyOtp } from '@/api/auth/register';
import LoginFormContainer from '@/components/auth/LoginFormContainer';
import { useStoreState } from 'easy-peasy';
import { Formik, FormikHelpers } from 'formik';
import { object, string, ref as yupRef } from 'yup';
import Field from '@/components/elements/Field';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import Captcha, { CaptchaRef } from '@/components/elements/Captcha';
import useFlash from '@/plugins/useFlash';

interface Values {
    email: string;
    username: string;
    name_first: string;
    name_last: string;
    password: string;
    password_confirmation: string;
    otp: string;
}

const RegisterContainer = ({ history }: RouteComponentProps) => {
    const ref = useRef<CaptchaRef>(null);
    const [token, setToken] = useState('');
    const [otpSent, setOtpSent] = useState(false);
    const [emailForOtp, setEmailForOtp] = useState('');
    const [isOtpSubmitting, setIsOtpSubmitting] = useState(false);

    const { clearFlashes, clearAndAddHttpError, addFlash } = useFlash();
    const { enabled: recaptchaEnabled, siteKey } = useStoreState((state) => state.settings.data!.recaptcha);
    const registrationEnabled = useStoreState((state) => state.settings.data?.registration?.enabled ?? false);
    const discordEnabled = useStoreState((state) => state.settings.data?.discord?.enabled ?? false);

    useEffect(() => {
        clearFlashes();
    }, []);

    // If registration is disabled, redirect to login page
    useEffect(() => {
        if (!registrationEnabled) {
            history.replace('/auth/login');
        }
    }, [registrationEnabled]);

    const onSubmit = (values: Values, { setSubmitting, setFieldValue }: FormikHelpers<Values>) => {
        clearFlashes();

        if (otpSent) {
            if (values.otp.length !== 6) {
                addFlash({
                    type: 'error',
                    message: 'Please enter a valid 6-digit code.',
                    key: 'login',
                });
                setSubmitting(false);
                return;
            }

            setIsOtpSubmitting(true);

            if (recaptchaEnabled && !token) {
                ref.current!.execute().catch((error) => {
                    console.error(error);
                    setIsOtpSubmitting(false);
                    setSubmitting(false);
                    clearAndAddHttpError({ error });
                });
                return;
            }

            verifyOtp(emailForOtp, values.otp, token)
                .then((response) => {
                    if (response.success) {
                        addFlash({
                            type: 'success',
                            message: 'Email verified successfully! Logging you in...',
                            key: 'login',
                        });
                        // @ts-expect-error this is valid
                        window.location = response.intended || '/';
                    }
                })
                .catch((error) => {
                    console.error(error);
                    setToken('');
                    if (ref.current) ref.current.reset();
                    setIsOtpSubmitting(false);
                    setSubmitting(false);
                    clearAndAddHttpError({ error });
                });
            return;
        }

        // Normal Registration Flow
        if (recaptchaEnabled && !token) {
            ref.current!.execute().catch((error) => {
                console.error(error);
                setSubmitting(false);
                clearAndAddHttpError({ error });
            });
            return;
        }

        register({ ...values, recaptchaData: token })
            .then((response) => {
                if (response.otp_required) {
                    setOtpSent(true);
                    setEmailForOtp(response.email);
                    setToken('');
                    if (ref.current) ref.current.reset();
                    setSubmitting(false);
                    addFlash({
                        type: 'info',
                        message: 'A 6-digit verification code has been sent to your email. Please enter it below to complete your registration.',
                        key: 'login',
                    });
                } else if (response.success) {
                    addFlash({
                        type: 'success',
                        message: 'Account created successfully! Logging you in...',
                        key: 'login',
                    });
                    // @ts-expect-error this is valid
                    window.location = response.intended || '/';
                }
            })
            .catch((error) => {
                console.error(error);
                setToken('');
                if (ref.current) ref.current.reset();
                setSubmitting(false);
                clearAndAddHttpError({ error });
            });
    };

    if (!registrationEnabled) {
        return null;
    }

    return (
        <Formik
            onSubmit={onSubmit}
            initialValues={{
                email: '',
                username: '',
                name_first: '',
                name_last: '',
                password: '',
                password_confirmation: '',
                otp: '',
            }}
            validationSchema={otpSent ? object().shape({
                otp: string().required('Verification code is required.').length(6, 'Verification code must be 6 digits.'),
            }) : object().shape({
                email: string().email('A valid email address must be provided.').required('An email address is required.'),
                username: string().required('A username is required.').min(3, 'Username must be at least 3 characters.').max(30, 'Username cannot exceed 30 characters.'),
                name_first: string().required('First name is required.').max(50, 'First name cannot exceed 50 characters.'),
                name_last: string().required('Last name is required.').max(50, 'Last name cannot exceed 50 characters.'),
                password: string().required('Please enter a password.').min(8, 'Password must be at least 8 characters.'),
                password_confirmation: string()
                    .required('Please confirm your password.')
                    .oneOf([yupRef('password')], 'Passwords must match.'),
            })}
        >
            {({ isSubmitting, submitForm, setFieldValue }) => (
                <LoginFormContainer title={otpSent ? 'Verify Your Email' : 'Create an Account'} css={tw`w-full flex`}>
                    {otpSent ? (
                        <>
                            <p css={tw`text-sm text-neutral-400 mb-6 text-center md:-mt-2`}>
                                A 6-digit verification code has been sent to <strong css={tw`text-purple-400`}>{emailForOtp}</strong>. Enter it below to complete your registration.
                            </p>
                            <div css={tw`mt-4`}>
                                <Field
                                    type={'text'}
                                    label={'Verification Code'}
                                    name={'otp'}
                                    maxLength={6}
                                    disabled={isOtpSubmitting}
                                />
                            </div>
                            <div css={tw`mt-6`}>
                                <Button
                                    type={'submit'}
                                    size={'xlarge'}
                                    isLoading={isOtpSubmitting}
                                    disabled={isOtpSubmitting}
                                    css={tw`w-full bg-purple-600 hover:bg-purple-700 border-purple-700 hover:border-purple-800 text-white font-bold tracking-wide rounded-xl shadow-lg transition-all transform active:scale-95`}
                                >
                                    Verify & Create Account
                                </Button>
                            </div>
                            <div css={tw`mt-6 text-center text-xs text-neutral-500`}>
                                <button
                                    type={'button'}
                                    onClick={() => {
                                        setOtpSent(false);
                                        setFieldValue('otp', '');
                                        clearFlashes();
                                    }}
                                    css={tw`text-purple-400 hover:text-purple-300 font-semibold no-underline transition-colors bg-transparent border-0 cursor-pointer p-0`}
                                >
                                    Back to Register
                                </button>
                            </div>
                        </>
                    ) : (
                        <>
                            <p css={tw`text-sm text-neutral-400 mb-6 text-center md:-mt-2`}>
                                Enter your details below to register your account.
                            </p>
                            <div css={tw`grid grid-cols-1 sm:grid-cols-2 gap-x-4`}>
                                <Field type={'text'} label={'First Name'} name={'name_first'} disabled={isSubmitting} />
                                <Field type={'text'} label={'Last Name'} name={'name_last'} disabled={isSubmitting} />
                            </div>
                            <div css={tw`grid grid-cols-1 sm:grid-cols-2 gap-x-4 mt-4`}>
                                <Field type={'text'} label={'Username'} name={'username'} disabled={isSubmitting} />
                                <Field type={'email'} label={'Email Address'} name={'email'} disabled={isSubmitting} />
                            </div>
                            <div css={tw`grid grid-cols-1 sm:grid-cols-2 gap-x-4 mt-4`}>
                                <Field type={'password'} label={'Password'} name={'password'} disabled={isSubmitting} />
                                <Field type={'password'} label={'Confirm Password'} name={'password_confirmation'} disabled={isSubmitting} />
                            </div>
                            <div css={tw`mt-6`}>
                                <Button
                                    type={'submit'}
                                    size={'xlarge'}
                                    isLoading={isSubmitting}
                                    disabled={isSubmitting}
                                    css={tw`w-full bg-purple-600 hover:bg-purple-700 border-purple-700 hover:border-purple-800 text-white font-bold tracking-wide rounded-xl shadow-lg transition-all transform active:scale-95`}
                                >
                                    Register
                                </Button>
                            </div>
                            <div css={tw`mt-6 text-center text-xs text-neutral-500`}>
                                Already have an account?{' '}
                                <Link
                                    to={'/auth/login'}
                                    css={tw`text-purple-400 hover:text-purple-300 font-semibold no-underline transition-colors`}
                                >
                                    Login
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
                                        Register with Discord
                                    </a>
                                </>
                            )}
                        </>
                    )}
                    <Captcha
                        ref={ref}
                        onVerify={(response) => {
                            setToken(response);
                            submitForm();
                        }}
                        onExpire={() => {
                            setToken('');
                        }}
                    />
                </LoginFormContainer>
            )}
        </Formik>
    );
};

export default RegisterContainer;
