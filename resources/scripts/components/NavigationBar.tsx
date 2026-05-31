import * as React from 'react';
import { useState, useEffect } from 'react';
import { Link, NavLink, useRouteMatch } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faCogs,
    faLayerGroup,
    faSignOutAlt,
    faBars,
    faTimes,
    faUser,
    faBell,
    faSun,
    faMoon,
    faHeadset,
    faTerminal,
    faFolderOpen,
    faDatabase,
    faCalendarAlt,
    faUsers,
    faArchive,
    faNetworkWired,
    faPlay,
    faSlidersH,
    faHistory,
    faExternalLinkAlt,
    faGlobe
} from '@fortawesome/free-solid-svg-icons';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import SearchContainer from '@/components/dashboard/search/SearchContainer';
import tw, { theme } from 'twin.macro';
import styled from 'styled-components/macro';
import http from '@/api/http';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { ServerContext } from '@/state/server';
import routes from '@/routers/routes';
import Can from '@/components/elements/Can';

const SidebarLink = styled(NavLink)`
    ${tw`flex items-center w-full px-4 py-2.5 text-neutral-400 hover:text-white hover:bg-neutral-800/40 rounded-lg transition-all duration-150 no-underline font-medium`};
    &.active {
        ${tw`text-white bg-purple-600 hover:bg-purple-700 shadow-md`};
    }
`;

const SidebarAnchor = styled.a`
    ${tw`flex items-center w-full px-4 py-2.5 text-neutral-400 hover:text-white hover:bg-neutral-800/40 rounded-lg transition-all duration-150 no-underline font-medium`};
`;

const SidebarButton = styled.button`
    ${tw`flex items-center w-full px-4 py-2.5 text-neutral-400 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-all duration-150 text-left font-medium`};
`;

const iconMap: Record<string, any> = {
    'Console': faTerminal,
    'Files': faFolderOpen,
    'Databases': faDatabase,
    'Schedules': faCalendarAlt,
    'Users': faUsers,
    'Backups': faArchive,
    'Network': faNetworkWired,
    'Startup': faPlay,
    'Settings': faSlidersH,
    'Activity': faHistory,
    'Subdomains': faGlobe,
};

const ServerSidebarLinks = ({ setSidebarOpen }: { setSidebarOpen: (o: boolean) => void }) => {
    const match = useRouteMatch<{ id: string }>('/server/:id');
    const rootAdmin = useStoreState((state: ApplicationStore) => state.user.data!.rootAdmin);

    if (!match) return null;

    return <ServerSidebarLinksInner match={match} rootAdmin={rootAdmin} setSidebarOpen={setSidebarOpen} />;
};

const ServerSidebarLinksInner = ({ match, rootAdmin, setSidebarOpen }: { match: any, rootAdmin: boolean, setSidebarOpen: (o: boolean) => void }) => {
    const serverName = ServerContext.useStoreState((state) => state.server.data?.name);
    const serverId = ServerContext.useStoreState((state) => state.server.data?.internalId);

    const to = (path: string) => {
        if (path === '/') {
            return `/server/${match.params.id}`;
        }
        return `/server/${match.params.id}/${path.replace(/^\/+/, '')}`;
    };

    return (
        <>
            <div className="h-px bg-neutral-800 my-4" />
            <div className="text-[10px] font-bold text-neutral-500 px-4 pb-2 tracking-wider uppercase truncate" title={serverName}>
                {serverName || 'Server Management'}
            </div>
            {routes.server
                .filter((route) => !!route.name)
                .map((route) => {
                    const icon = iconMap[route.name!] || faLayerGroup;
                    const content = (
                        <SidebarLink
                            key={route.path}
                            to={to(route.path)}
                            exact={route.exact}
                            onClick={() => setSidebarOpen(false)}
                        >
                            <FontAwesomeIcon icon={icon} css={tw`w-5 mr-4 text-center`} />
                            <span>{route.name}</span>
                        </SidebarLink>
                    );

                    return route.permission ? (
                        <Can key={route.path} action={route.permission} matchAny>
                            {content}
                        </Can>
                    ) : (
                        content
                    );
                })}
        </>
    );
};

export default () => {
    const name = useStoreState((state: ApplicationStore) => state.settings.data!.name);
    const rootAdmin = useStoreState((state: ApplicationStore) => state.user.data!.rootAdmin);
    const discordUrl = useStoreState((state: ApplicationStore) => state.settings.data!.theme?.discord_url);
    const supportUrl = useStoreState((state: ApplicationStore) => state.settings.data!.theme?.support_url);
    const [isLoggingOut, setIsLoggingOut] = useState(false);
    const [sidebarOpen, setSidebarOpen] = useState(false);

    useEffect(() => {
        document.body.classList.remove('light-mode');
        document.documentElement.classList.remove('light-mode');
        localStorage.removeItem('theme');
    }, []);

    const onTriggerLogout = () => {
        setIsLoggingOut(true);
        http.post('/auth/logout').finally(() => {
            // @ts-expect-error this is valid
            window.location = '/';
        });
    };

    const DiscordIcon = () => (
        <svg viewBox="0 0 127.14 96.36" style={{ width: '18px', height: '18px', fill: 'currentColor' }}>
            <path d="M107.7,8.07A105.15,105.15,0,0,0,77.26,0a77.19,77.19,0,0,0-3.3,6.83A96.67,96.67,0,0,0,53.22,6.83,77.19,77.19,0,0,0,49.88,0,105.15,105.15,0,0,0,19.44,8.07C3.66,31.58-1.86,54.65,1,77.53A105.73,105.73,0,0,0,32,96.36a77.7,77.7,0,0,0,6.63-10.85,68.43,68.43,0,0,1-10.43-5c.87-.64,1.71-1.32,2.51-2a76.1,76.1,0,0,0,72.76,0c.8,0.7,1.64,1.38,2.51,2a68.43,68.43,0,0,1-10.43,5,77.7,77.7,0,0,0,6.63,10.85,105.73,105.73,0,0,0,31-18.83C129.87,50.22,123.63,27.31,107.7,8.07ZM42.45,65.69C36.18,65.69,31,60,31,53S36.18,40.36,42.45,40.36,53.83,46,53.83,53,48.72,65.69,42.45,65.69Zm42.24,0C78.41,65.69,73.24,60,73.24,53S78.41,40.36,84.69,40.36,96.07,46,96.07,53,91,65.69,84.69,65.69Z" />
        </svg>
    );

    return (
        <>
            <SpinnerOverlay visible={isLoggingOut} />

            {/* Mobile & Desktop Header Topbar */}
            <div
                className="light-topbar fixed top-0 right-0 h-16 bg-[#0b0c16] border-b border-neutral-800 flex items-center justify-between px-4 md:px-6 z-40 left-0 md:left-64"
            >
                {/* Left Side: Mobile Hamburger button & Name */}
                <div css={tw`flex items-center`}>
                    <button
                        onClick={() => setSidebarOpen(!sidebarOpen)}
                        className="w-10 h-10 flex items-center justify-center rounded-lg bg-neutral-900 border border-neutral-800 text-neutral-200 hover:text-white mr-3 md:hidden focus:outline-none"
                    >
                        <FontAwesomeIcon icon={sidebarOpen ? faTimes : faBars} size="lg" />
                    </button>
                    <Link to="/" css={tw`text-lg font-header font-bold text-white tracking-tight truncate max-w-[120px] no-underline md:hidden`}>
                        {name}
                    </Link>
                </div>

                {/* Right Side: Grouped Boxed Icons */}
                <div css={tw`flex items-center space-x-2 flex-shrink-0`}>
                    {discordUrl && (
                        <a href={discordUrl} target="_blank" rel="noopener noreferrer" className="w-10 h-10 flex items-center justify-center rounded-lg bg-neutral-900 border border-neutral-800 text-neutral-400 hover:text-purple-400 hover:bg-neutral-800 transition-all duration-150" title="Join Discord">
                            <DiscordIcon />
                        </a>
                    )}
                    <NavLink to="/account/activity" className="w-10 h-10 flex items-center justify-center rounded-lg bg-neutral-900 border border-neutral-800 text-neutral-400 hover:text-purple-400 hover:bg-neutral-800 transition-all duration-150" title="Activity / Notifications">
                        <FontAwesomeIcon icon={faBell} size="lg" />
                    </NavLink>
                    {supportUrl && (
                        <a href={supportUrl} target="_blank" rel="noopener noreferrer" className="w-10 h-10 flex items-center justify-center rounded-lg bg-neutral-900 border border-neutral-800 text-neutral-400 hover:text-purple-400 hover:bg-neutral-800 transition-all duration-150" title="Support Server">
                            <FontAwesomeIcon icon={faHeadset} size="lg" />
                        </a>
                    )}
                </div>
            </div>

            {/* Mobile Drawer Backdrop */}
            {sidebarOpen && (
                <div onClick={() => setSidebarOpen(false)} css={tw`fixed inset-0 bg-black bg-opacity-60 z-40 md:hidden`} />
            )}

            {/* Sidebar Navigation */}
            <div
                className={`fixed top-0 left-0 bottom-0 w-64 bg-[#0b0c16] border-r border-neutral-800 flex flex-col z-50 transition-transform duration-200 ease-in-out theme-sidebar ${
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'
                }`}
            >
                {/* Sidebar Header */}
                <div css={tw`flex items-center h-16 px-6 border-b border-neutral-800 bg-[#07080e]`}>
                    <Link to="/" onClick={() => setSidebarOpen(false)} css={tw`text-xl font-header font-bold text-white tracking-tight truncate no-underline`}>
                        {name}
                    </Link>
                </div>


                {/* Navigation Links */}
                <div css={tw`flex-1 overflow-y-auto px-3 py-4 space-y-1`}>
                    <div className="text-[10px] font-bold text-neutral-500 px-4 pb-2 tracking-wider uppercase">
                        Global
                    </div>
                    <SidebarLink to="/" exact onClick={() => setSidebarOpen(false)}>
                        <FontAwesomeIcon icon={faLayerGroup} css={tw`w-5 mr-4 text-center`} />
                        <span>Dashboard</span>
                    </SidebarLink>

                    <SidebarLink to="/account" onClick={() => setSidebarOpen(false)}>
                        <FontAwesomeIcon icon={faUser} css={tw`w-5 mr-4 text-center`} />
                        <span>Account Settings</span>
                    </SidebarLink>

                    <ServerSidebarLinks setSidebarOpen={setSidebarOpen} />

                    {rootAdmin && (
                        <>
                            <div className="h-px bg-neutral-800 my-4" />
                            <div className="text-[10px] font-bold text-neutral-500 px-4 pb-2 tracking-wider uppercase">
                                Administration
                            </div>
                            <SidebarAnchor href="/admin" rel="noreferrer">
                                <FontAwesomeIcon icon={faCogs} css={tw`w-5 mr-4 text-center`} />
                                <span>Admin Panel</span>
                            </SidebarAnchor>
                        </>
                    )}
                </div>

                {/* Sidebar Footer (Sign Out) */}
                <div css={tw`border-t border-neutral-800 p-3`}>
                    <SidebarButton onClick={onTriggerLogout}>
                        <FontAwesomeIcon icon={faSignOutAlt} css={tw`w-5 mr-4 text-center`} />
                        <span>Sign Out</span>
                    </SidebarButton>
                </div>
            </div>
        </>
    );
};
