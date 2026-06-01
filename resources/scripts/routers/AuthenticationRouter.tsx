import React from 'react';
import { Route, Switch, useRouteMatch } from 'react-router-dom';
import LoginContainer from '@/components/auth/LoginContainer';
import RegisterContainer from '@/components/auth/RegisterContainer';
import ForgotPasswordContainer from '@/components/auth/ForgotPasswordContainer';
import ResetPasswordContainer from '@/components/auth/ResetPasswordContainer';
import LoginCheckpointContainer from '@/components/auth/LoginCheckpointContainer';
import { NotFound } from '@/components/elements/ScreenBlock';
import { useHistory, useLocation } from 'react-router';
import tw from 'twin.macro';

export default () => {
    const history = useHistory();
    const location = useLocation();
    const { path } = useRouteMatch();

    return (
        <div css={tw`fixed inset-0 flex items-center justify-center bg-[#07080f] overflow-y-auto`}>
            {/* Background radial glow */}
            <div 
                style={{
                    position: 'absolute',
                    inset: 0,
                    backgroundImage: 'radial-gradient(circle at center, rgba(139, 92, 246, 0.08) 0%, transparent 80%)',
                    pointerEvents: 'none',
                }}
            />
            {/* Background grid overlay */}
            <div 
                style={{
                    position: 'absolute',
                    inset: 0,
                    backgroundImage: 'linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px), linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px)',
                    backgroundSize: '4rem 4rem',
                    pointerEvents: 'none',
                }}
            />
            <div css={tw`relative w-full z-10 p-4 flex justify-center items-center`}>
                <Switch location={location}>
                    <Route path={`${path}/login`} component={LoginContainer} exact />
                    <Route path={`${path}/register`} component={RegisterContainer} exact />
                    <Route path={`${path}/login/checkpoint`} component={LoginCheckpointContainer} />
                    <Route path={`${path}/password`} component={ForgotPasswordContainer} exact />
                    <Route path={`${path}/password/reset/:token`} component={ResetPasswordContainer} />
                    <Route path={`${path}/checkpoint`} />
                    <Route path={'*'}>
                        <NotFound onBack={() => history.push('/auth/login')} />
                    </Route>
                </Switch>
            </div>
        </div>
    );
};
