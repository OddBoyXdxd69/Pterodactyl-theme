import React, { useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import useFlash from '@/plugins/useFlash';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import FlashMessageRender from '@/components/FlashMessageRender';
import http from '@/api/http';
import tw from 'twin.macro';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faDownload,
    faChevronLeft,
    faSearch,
    faTimes,
    faInfoCircle,
    faExclamationTriangle,
    faCheckCircle,
    faFolderOpen
} from '@fortawesome/free-solid-svg-icons';
import Button from '@/components/elements/Button';
import Input from '@/components/elements/Input';
import Spinner from '@/components/elements/Spinner';
import GreyRowBox from '@/components/elements/GreyRowBox';

interface Provider {
    id: string;
    name: string;
    description: string;
    type: 'server' | 'proxy';
    defaultFilename: string;
    logo: React.ReactNode;
}

interface VersionItem {
    id: string; // unique identifier
    name: string; // display name e.g. "1.21.1" or "Build #1850"
    versionNumber: string;
    date?: string;
    details?: string;
    downloadUrl?: string; // If known directly
}

export default () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const { clearAndAddHttpError, clearFlashes, addFlash } = useFlash();

    const [selectedProvider, setSelectedProvider] = useState<Provider | null>(null);
    const [versions, setVersions] = useState<VersionItem[]>([]);
    const [filteredVersions, setFilteredVersions] = useState<VersionItem[]>([]);
    const [searchQuery, setSearchQuery] = useState('');
    const [loadingVersions, setLoadingVersions] = useState(false);
    const [visibleCount, setVisibleCount] = useState(20);

    // Modal install options
    const [showInstallModal, setShowInstallModal] = useState(false);
    const [selectedVersion, setSelectedVersion] = useState<VersionItem | null>(null);
    const [destinationFilename, setDestinationFilename] = useState('server.jar');
    const [isInstalling, setIsInstalling] = useState(false);

    const providers: Provider[] = [
        {
            id: 'paper',
            name: 'Paper',
            description: 'The most popular high-performance Spigot-compatible server software with extensive plugin support.',
            type: 'server',
            defaultFilename: 'server.jar',
            logo: (
                <svg className="w-12 h-12 flex-shrink-0" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="512" height="512" rx="128" fill="url(#paper-grad)"/>
                    <path d="M128 384L384 256L128 128V224L288 256L128 288V384Z" fill="white"/>
                    <defs>
                        <linearGradient id="paper-grad" x1="0" y1="0" x2="512" y2="512" gradientUnits="userSpaceOnUse">
                            <stop stopColor="#00b4db"/>
                            <stop offset="1" stopColor="#0083b0"/>
                        </linearGradient>
                    </defs>
                </svg>
            )
        },
        {
            id: 'folia',
            name: 'Folia',
            description: 'A new multi-threaded Minecraft server software from PaperMC designed to support massive player counts.',
            type: 'server',
            defaultFilename: 'server.jar',
            logo: (
                <svg className="w-12 h-12 flex-shrink-0" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="512" height="512" rx="128" fill="url(#folia-grad)"/>
                    <path d="M256 96C160 96 112 192 112 288C112 352 160 400 224 400C240 400 256 384 256 368V96Z" fill="white" opacity="0.9"/>
                    <path d="M256 96C352 96 400 192 400 288C400 352 352 400 288 400C272 400 256 384 256 368V96Z" fill="white"/>
                    <path d="M256 416V96" stroke="#11998e" strokeWidth="16" strokeLinecap="round"/>
                    <defs>
                        <linearGradient id="folia-grad" x1="0" y1="0" x2="512" y2="512" gradientUnits="userSpaceOnUse">
                            <stop stopColor="#11998e"/>
                            <stop offset="1" stopColor="#38ef7d"/>
                        </linearGradient>
                    </defs>
                </svg>
            )
        },
        {
            id: 'purpur',
            name: 'Purpur',
            description: 'A drop-in replacement for Paper designed for configurability, performance, and fun gameplay settings.',
            type: 'server',
            defaultFilename: 'server.jar',
            logo: (
                <svg className="w-12 h-12 flex-shrink-0" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="512" height="512" rx="128" fill="url(#purpur-grad)"/>
                    <path d="M256 128L352 192V320L256 384L160 320V192L256 128Z" stroke="white" strokeWidth="24" strokeLinejoin="round" fill="none"/>
                    <path d="M256 128V384" stroke="white" strokeWidth="16"/>
                    <path d="M160 192L352 320" stroke="white" strokeWidth="16"/>
                    <path d="M352 192L160 320" stroke="white" strokeWidth="16"/>
                    <defs>
                        <linearGradient id="purpur-grad" x1="0" y1="0" x2="512" y2="512" gradientUnits="userSpaceOnUse">
                            <stop stopColor="#8a2387"/>
                            <stop offset="0.5" stopColor="#e94057"/>
                            <stop offset="1" stopColor="#f27121"/>
                        </linearGradient>
                    </defs>
                </svg>
            )
        },
        {
            id: 'vanilla',
            name: 'Vanilla',
            description: 'The official vanilla Minecraft server software straight from Mojang. Standard experience without mods/plugins.',
            type: 'server',
            defaultFilename: 'server.jar',
            logo: (
                <svg className="w-12 h-12 flex-shrink-0" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="512" height="512" rx="128" fill="url(#vanilla-grad)"/>
                    <path d="M256 128L384 192L256 256L128 192L256 128Z" fill="#7ebd42"/>
                    <path d="M128 192L256 256V384L128 320V192Z" fill="#866043"/>
                    <path d="M256 256L384 192V320L256 384V256Z" fill="#5c3e29"/>
                    <path d="M128 192L160 216L192 208L224 232L256 256L288 232L320 240L352 216L384 192L384 210L352 230L320 250L288 240L256 270L224 250L192 245L160 230L128 210V192Z" fill="#7ebd42"/>
                    <defs>
                        <linearGradient id="vanilla-grad" x1="0" y1="0" x2="512" y2="512" gradientUnits="userSpaceOnUse">
                            <stop stopColor="#4568dc"/>
                            <stop offset="1" stopColor="#b06ab3"/>
                        </linearGradient>
                    </defs>
                </svg>
            )
        },
        {
            id: 'fabric',
            name: 'Fabric',
            description: 'A lightweight and highly customizable modding engine compatible with hundreds of modern Fabric mods.',
            type: 'server',
            defaultFilename: 'server.jar',
            logo: (
                <svg className="w-12 h-12 flex-shrink-0" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="512" height="512" rx="128" fill="url(#fabric-grad)"/>
                    <path d="M160 160H352V352H160V160Z" stroke="white" strokeWidth="24" strokeLinejoin="round" fill="none"/>
                    <path d="M160 224H352" stroke="white" strokeWidth="16" strokeDasharray="16 16"/>
                    <path d="M160 288H352" stroke="white" strokeWidth="16" strokeDasharray="16 16"/>
                    <path d="M224 160V352" stroke="white" strokeWidth="16" strokeDasharray="16 16"/>
                    <path d="M288 160V352" stroke="white" strokeWidth="16" strokeDasharray="16 16"/>
                    <defs>
                        <linearGradient id="fabric-grad" x1="0" y1="0" x2="512" y2="512" gradientUnits="userSpaceOnUse">
                            <stop stopColor="#e65c00"/>
                            <stop offset="1" stopColor="#F9D423"/>
                        </linearGradient>
                    </defs>
                </svg>
            )
        },
        {
            id: 'velocity',
            name: 'Velocity',
            description: 'The next-generation Minecraft proxy software designed for extreme performance, security, and flexibility.',
            type: 'proxy',
            defaultFilename: 'velocity.jar',
            logo: (
                <svg className="w-12 h-12 flex-shrink-0" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="512" height="512" rx="128" fill="url(#velocity-grad)"/>
                    <path d="M160 160L320 256L160 352V296L256 256L160 216V160Z" fill="white"/>
                    <path d="M256 160L416 256L256 352V296L352 256L256 216V160Z" fill="white" opacity="0.7"/>
                    <defs>
                        <linearGradient id="velocity-grad" x1="0" y1="0" x2="512" y2="512" gradientUnits="userSpaceOnUse">
                            <stop stopColor="#36d1dc"/>
                            <stop offset="1" stopColor="#5b86e5"/>
                        </linearGradient>
                    </defs>
                </svg>
            )
        },
        {
            id: 'bungeecord',
            name: 'BungeeCord',
            description: 'The legendary proxy software by SpigotMC that connects multiple servers together in a network.',
            type: 'proxy',
            defaultFilename: 'BungeeCord.jar',
            logo: (
                <svg className="w-12 h-12 flex-shrink-0" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="512" height="512" rx="128" fill="url(#bungee-grad)"/>
                    <path d="M192 128C192 128 256 192 256 256C256 320 320 384 320 384" stroke="white" strokeWidth="32" strokeLinecap="round" fill="none"/>
                    <circle cx="192" cy="128" r="24" fill="white"/>
                    <circle cx="320" cy="384" r="24" fill="white"/>
                    <path d="M224 256H288" stroke="white" strokeWidth="16" strokeLinecap="round"/>
                    <defs>
                        <linearGradient id="bungee-grad" x1="0" y1="0" x2="512" y2="512" gradientUnits="userSpaceOnUse">
                            <stop stopColor="#ff9966"/>
                            <stop offset="1" stopColor="#ff5e62"/>
                        </linearGradient>
                    </defs>
                </svg>
            )
        }
    ];

    useEffect(() => {
        if (!selectedProvider) return;

        clearFlashes('versions');
        setLoadingVersions(true);
        setVersions([]);
        setSearchQuery('');
        setVisibleCount(20);

        const id = selectedProvider.id;

        if (id === 'paper' || id === 'folia' || id === 'velocity') {
            // PaperMC projects API
            fetch(`https://api.papermc.io/v2/projects/${id}`)
                .then((res) => {
                    if (!res.ok) throw new Error('API request failed');
                    return res.json();
                })
                .then((data) => {
                    const list: VersionItem[] = (data.versions || [])
                        .filter((v: string) => !v.includes('-'))
                        .map((v: string) => ({
                            id: v,
                            name: v,
                            versionNumber: v,
                        }))
                        .reverse(); // Newest first
                    setVersions(list);
                })
                .catch((err) => {
                    console.error(err);
                    addFlash({ key: 'versions', type: 'danger', message: `Failed to load versions for ${selectedProvider.name} from PaperMC API.` });
                })
                .finally(() => setLoadingVersions(false));
        } else if (id === 'purpur') {
            // Purpur API
            fetch('https://api.purpurmc.org/v2/purpur')
                .then((res) => {
                    if (!res.ok) throw new Error('API request failed');
                    return res.json();
                })
                .then((data) => {
                    const list: VersionItem[] = (data.versions || [])
                        .filter((v: string) => !v.includes('-'))
                        .map((v: string) => ({
                            id: v,
                            name: v,
                            versionNumber: v,
                        }))
                        .reverse(); // Newest first
                    setVersions(list);
                })
                .catch((err) => {
                    console.error(err);
                    addFlash({ key: 'versions', type: 'danger', message: `Failed to load versions for Purpur from PurpurMC API.` });
                })
                .finally(() => setLoadingVersions(false));
        } else if (id === 'vanilla') {
            // Mojang Manifest
            fetch('https://launchermeta.mojang.com/mc/game/version_manifest.json')
                .then((res) => {
                    if (!res.ok) throw new Error('API request failed');
                    return res.json();
                })
                .then((data) => {
                    const list: VersionItem[] = (data.versions || [])
                        .filter((v: any) => v.type === 'release')
                        .map((v: any) => ({
                            id: v.id,
                            name: v.id,
                            versionNumber: v.id,
                            details: `Mojang Package Manifest JSON: ${v.url}`,
                        }));
                    setVersions(list);
                })
                .catch((err) => {
                    console.error(err);
                    addFlash({ key: 'versions', type: 'danger', message: `Failed to load versions for Vanilla from Mojang API.` });
                })
                .finally(() => setLoadingVersions(false));
        } else if (id === 'fabric') {
            // Fabric Meta
            fetch('https://meta.fabricmc.net/v2/versions/game')
                .then((res) => {
                    if (!res.ok) throw new Error('API request failed');
                    return res.json();
                })
                .then((data) => {
                    const list: VersionItem[] = (data || [])
                        .filter((v: any) => v.stable === true)
                        .map((v: any) => ({
                            id: v.version,
                            name: v.version,
                            versionNumber: v.version,
                        }));
                    setVersions(list);
                })
                .catch((err) => {
                    console.error(err);
                    addFlash({ key: 'versions', type: 'danger', message: `Failed to load Fabric compatible game versions.` });
                })
                .finally(() => setLoadingVersions(false));
        } else if (id === 'bungeecord') {
            // Spigot Jenkins Builds
            // Use tree parameter to pull the most recent builds only
            fetch('https://ci.md-5.net/job/BungeeCord/api/json?tree=builds[number,result,timestamp]{0,100}')
                .then((res) => {
                    if (!res.ok) throw new Error('API request failed');
                    return res.json();
                })
                .then((data) => {
                    const list: VersionItem[] = (data.builds || [])
                        .filter((b: any) => b.result === 'SUCCESS' || !b.result)
                        .map((b: any) => ({
                            id: String(b.number),
                            name: `Build #${b.number}`,
                            versionNumber: `Build #${b.number}`,
                            date: b.timestamp ? new Date(b.timestamp).toLocaleDateString() : 'Successful Build',
                            downloadUrl: `https://ci.md-5.net/job/BungeeCord/${b.number}/artifact/bootstrap/target/BungeeCord.jar`,
                        }));
                    setVersions(list);
                })
                .catch((err) => {
                    console.error(err);
                    addFlash({ key: 'versions', type: 'danger', message: `Failed to fetch BungeeCord builds from Spigot Jenkins API.` });
                })
                .finally(() => setLoadingVersions(false));
        }
    }, [selectedProvider]);

    useEffect(() => {
        if (!searchQuery) {
            setFilteredVersions(versions);
            return;
        }

        const filtered = versions.filter((v) =>
            v.versionNumber.toLowerCase().includes(searchQuery.toLowerCase())
        );
        setFilteredVersions(filtered);
    }, [searchQuery, versions]);

    const selectProvider = (provider: Provider) => {
        setSelectedProvider(provider);
    };

    const backToProviders = () => {
        setSelectedProvider(null);
        setVersions([]);
        setFilteredVersions([]);
    };

    const openInstallModal = (version: VersionItem) => {
        setSelectedVersion(version);
        setDestinationFilename(selectedProvider?.defaultFilename || 'server.jar');
        setShowInstallModal(true);
    };

    const triggerDownload = async () => {
        if (!selectedProvider || !selectedVersion) return;
        setIsInstalling(true);
        clearFlashes('versions');

        try {
            let downloadUrl = '';

            const id = selectedProvider.id;
            const version = selectedVersion.versionNumber;

            if (id === 'paper' || id === 'folia' || id === 'velocity') {
                // 1. Fetch builds to find the latest build number and its filename
                const buildsRes = await fetch(`https://api.papermc.io/v2/projects/${id}/versions/${version}/builds`);
                if (!buildsRes.ok) throw new Error('Failed to retrieve builds for this version.');
                const buildsData = await buildsRes.json();
                
                const latestBuildObj = buildsData.builds?.[buildsData.builds.length - 1];
                if (!latestBuildObj) throw new Error('No build available for this game version.');

                const buildNumber = latestBuildObj.build;
                const filename = latestBuildObj.downloads?.application?.name;

                if (!buildNumber || !filename) throw new Error('Build files are not fully resolved on PaperMC registry.');

                downloadUrl = `https://api.papermc.io/v2/projects/${id}/versions/${version}/builds/${buildNumber}/downloads/${filename}`;
            } else if (id === 'purpur') {
                // Purpur can download directly via 'latest' alias
                downloadUrl = `https://api.purpurmc.org/v2/purpur/${version}/latest/download`;
            } else if (id === 'vanilla') {
                // Fetch Minecraft meta URL
                const detailRes = await fetch(selectedVersion.details || `https://launchermeta.mojang.com/mc/game/version_manifest.json`);
                if (!detailRes.ok) throw new Error('Failed to resolve Vanilla package manifest.');
                const detailData = await detailRes.json();
                
                // If we fetched the manifest because details URL was missing:
                if (detailData.versions) {
                    const matchedVersion = detailData.versions.find((v: any) => v.id === version);
                    if (!matchedVersion) throw new Error('Vanilla version mismatch.');
                    const nestedRes = await fetch(matchedVersion.url);
                    const nestedData = await nestedRes.json();
                    downloadUrl = nestedData.downloads?.server?.url;
                } else {
                    downloadUrl = detailData.downloads?.server?.url;
                }

                if (!downloadUrl) throw new Error('No vanilla server download URL available for this version.');
            } else if (id === 'fabric') {
                // 1. Fetch compatible loader version
                const loaderRes = await fetch(`https://meta.fabricmc.net/v2/versions/loader/${version}`);
                if (!loaderRes.ok) throw new Error('Failed to retrieve compatible loaders.');
                const loaderData = await loaderRes.json();
                const loaderVersion = loaderData[0]?.loader?.version;
                if (!loaderVersion) throw new Error('No compatible Fabric Loader found for this version.');

                // 2. Fetch stable installer
                const installerRes = await fetch('https://meta.fabricmc.net/v2/versions/installer');
                if (!installerRes.ok) throw new Error('Failed to fetch Fabric installers list.');
                const installerData = await installerRes.json();
                const installerVersion = installerData.find((i: any) => i.stable === true)?.version || installerData[0]?.version;
                if (!installerVersion) throw new Error('No compatible Fabric Installer found.');

                // 3. Construct download URL
                downloadUrl = `https://meta.fabricmc.net/v2/versions/loader/${version}/${loaderVersion}/${installerVersion}/server/jar`;
            } else if (id === 'bungeecord') {
                // BungeeCord has its direct download link resolved from selection
                downloadUrl = selectedVersion.downloadUrl || '';
            }

            if (!downloadUrl) throw new Error('Failed to construct server jar download URL.');

            // Call Wings API
            await http.post(`/api/client/servers/${uuid}/files/pull`, {
                url: downloadUrl,
                directory: '', // Root folder
                filename: destinationFilename,
            });

            addFlash({
                key: 'versions',
                type: 'success',
                message: `Successfully pulled ${selectedProvider.name} ${selectedVersion.name} and saved as "${destinationFilename}" in your root directory! Restart your server to apply.`,
            });
            setShowInstallModal(false);
        } catch (err: any) {
            console.error(err);
            clearAndAddHttpError({ key: 'versions', error: err });
        } finally {
            setIsInstalling(false);
        }
    };

    return (
        <ServerContentBlock title={'Server Jar Versions Downloader'}>
            <FlashMessageRender byKey={'versions'} css={tw`mb-4`} />

            {!selectedProvider ? (
                <div css={tw`space-y-6`}>
                    <div css={tw`bg-neutral-900 border border-neutral-800 rounded-lg p-6 flex flex-col md:flex-row items-center gap-6`}>
                        <div css={tw`bg-purple-500/10 p-4 rounded-full text-purple-400 text-3xl flex justify-center items-center`}>
                            <FontAwesomeIcon icon={faFolderOpen} />
                        </div>
                        <div>
                            <h2 css={tw`text-xl font-bold text-gray-100`}>Manage Server Core Software</h2>
                            <p css={tw`text-sm text-neutral-400 mt-1`}>
                                Browse and install server executable jars directly from public build servers. Select a provider below to choose a version to download directly into your root directory.
                            </p>
                        </div>
                    </div>

                    <div css={tw`grid grid-cols-1 md:grid-cols-2 gap-4`}>
                        {providers.map((p) => (
                            <GreyRowBox
                                key={p.id}
                                onClick={() => selectProvider(p)}
                                css={tw`flex items-start p-5 bg-neutral-900 border border-neutral-800 hover:border-purple-500 hover:bg-neutral-900/80 transition-all duration-150 rounded-lg cursor-pointer gap-5`}
                            >
                                {p.logo}
                                <div css={tw`flex-1 min-w-0`}>
                                    <div css={tw`flex items-center gap-2`}>
                                        <h3 css={tw`font-bold text-gray-100 text-base`}>{p.name}</h3>
                                        <span css={[
                                            tw`text-[10px] uppercase font-semibold px-2 py-0.5 rounded`,
                                            p.type === 'server' ? tw`bg-blue-500/10 text-blue-400` : tw`bg-yellow-500/10 text-yellow-400`
                                        ]}>
                                            {p.type}
                                        </span>
                                    </div>
                                    <p css={tw`text-xs text-neutral-400 mt-2 line-clamp-2`}>
                                        {p.description}
                                    </p>
                                </div>
                            </GreyRowBox>
                        ))}
                    </div>
                </div>
            ) : (
                <div css={tw`space-y-6`}>
                    {/* Header Controls */}
                    <div css={tw`flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4`}>
                        <Button onClick={backToProviders} css={tw`flex items-center space-x-2`} color={'grey'}>
                            <FontAwesomeIcon icon={faChevronLeft} />
                            <span>Back to Providers</span>
                        </Button>
                        <div css={tw`flex items-center space-x-4`}>
                            {selectedProvider.logo}
                            <div>
                                <h2 css={tw`text-lg font-bold text-gray-100`}>{selectedProvider.name} Versions</h2>
                                <p css={tw`text-xs text-neutral-400`}>Select a version/build to install</p>
                            </div>
                        </div>
                    </div>

                    {/* Filter Input */}
                    <div css={tw`relative w-full`}>
                        <Input
                            placeholder={`Filter ${selectedProvider.name} versions... (e.g. 1.21 or 1.20)`}
                            type={'text'}
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            css={tw`pl-10 w-full`}
                        />
                        <div css={tw`absolute left-3.5 top-3.5 text-neutral-400`}>
                            <FontAwesomeIcon icon={faSearch} />
                        </div>
                    </div>

                    {/* Versions Grid */}
                    <div css={tw`space-y-3`}>
                        {loadingVersions ? (
                            <div css={tw`flex flex-col items-center justify-center py-16 bg-neutral-900 border border-neutral-800 rounded-lg`}>
                                <Spinner size={'large'} />
                                <p css={tw`text-sm text-neutral-400 mt-4`}>Resolving version trees, please wait...</p>
                            </div>
                        ) : filteredVersions.length === 0 ? (
                            <div css={tw`bg-neutral-900 border border-neutral-800 rounded-lg py-16 text-center text-neutral-400`}>
                                <FontAwesomeIcon icon={faInfoCircle} size={'3x'} css={tw`text-neutral-700 mb-3`} />
                                <p css={tw`text-base font-semibold text-neutral-200`}>No versions matched your filter</p>
                            </div>
                        ) : (
                            <>
                                <div css={tw`grid grid-cols-1 md:grid-cols-2 gap-4`}>
                                    {filteredVersions.slice(0, visibleCount).map((v) => (
                                        <GreyRowBox
                                            key={v.id}
                                            css={tw`flex items-center justify-between p-4 bg-neutral-900 border border-neutral-800 rounded-lg hover:border-purple-500 hover:bg-neutral-900/80 transition-all duration-150`}
                                        >
                                            <div css={tw`flex flex-col min-w-0 pr-4`}>
                                                <strong css={tw`text-purple-400 text-base font-mono`}>{v.name}</strong>
                                                {v.date && (
                                                    <span css={tw`text-[10px] text-neutral-400 mt-1`}>
                                                        Build Date: {v.date}
                                                    </span>
                                                )}
                                            </div>
                                            <Button
                                                onClick={() => openInstallModal(v)}
                                                css={tw`p-2 px-4 flex items-center space-x-2`}
                                            >
                                                <FontAwesomeIcon icon={faDownload} />
                                                <span>Install</span>
                                            </Button>
                                        </GreyRowBox>
                                    ))}
                                </div>

                                {filteredVersions.length > visibleCount && (
                                    <div css={tw`flex justify-center pt-2`}>
                                        <Button onClick={() => setVisibleCount((prev) => prev + 20)} css={tw`w-full`}>
                                            Show More Versions
                                        </Button>
                                    </div>
                                )}
                            </>
                        )}
                    </div>
                </div>
            )}

            {/* Install Modal Dialog */}
            {showInstallModal && selectedProvider && selectedVersion && (
                <div css={tw`fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm`}>
                    <div css={tw`bg-neutral-900 border border-neutral-800 rounded-lg max-w-lg w-full p-6 relative flex flex-col gap-4`}>
                        {/* Header */}
                        <div css={tw`flex justify-between items-center pb-3 border-b border-neutral-800`}>
                            <h3 css={tw`text-lg font-bold text-gray-100 flex items-center gap-2`}>
                                <FontAwesomeIcon icon={faExclamationTriangle} css={tw`text-yellow-500`} />
                                <span>Install {selectedProvider.name} {selectedVersion.name}</span>
                            </h3>
                            <button onClick={() => setShowInstallModal(false)} css={tw`text-neutral-400 hover:text-white focus:outline-none`} disabled={isInstalling}>
                                <FontAwesomeIcon icon={faTimes} size={'lg'} />
                            </button>
                        </div>

                        {/* Warnings */}
                        <div css={tw`bg-yellow-500/10 border-l-4 border-yellow-500 p-4 rounded text-sm text-yellow-200 flex items-start gap-3`}>
                            <FontAwesomeIcon icon={faExclamationTriangle} css={tw`mt-1 flex-shrink-0`} />
                            <div>
                                <p css={tw`font-bold`}>Warning: Potential File Overwrite</p>
                                <p css={tw`mt-1 text-xs text-yellow-300`}>
                                    This action will download the server executable jar file directly into your server root directory. If a file with the same name exists, it will be completely overwritten!
                                </p>
                            </div>
                        </div>

                        {/* File Name Config */}
                        <div css={tw`flex flex-col gap-2`}>
                            <label css={tw`text-xs font-bold text-neutral-400 uppercase`}>Destination Filename</label>
                            <Input
                                type="text"
                                value={destinationFilename}
                                onChange={(e) => setDestinationFilename(e.target.value)}
                                placeholder="e.g. server.jar"
                                disabled={isInstalling}
                            />
                            <p css={tw`text-[10px] text-neutral-500`}>
                                Make sure this matches the filename defined in your server's startup command (e.g. <code>server.jar</code>).
                            </p>
                        </div>

                        {/* Footer Controls */}
                        <div css={tw`flex justify-end gap-3 pt-3 border-t border-neutral-800`}>
                            <Button color={'grey'} onClick={() => setShowInstallModal(false)} disabled={isInstalling}>
                                Cancel
                            </Button>
                            {isInstalling ? (
                                <Button disabled css={tw`flex items-center space-x-2`}>
                                    <Spinner size={'small'} />
                                    <span>Installing Jar...</span>
                                </Button>
                            ) : (
                                <Button onClick={triggerDownload} css={tw`flex items-center space-x-2`}>
                                    <FontAwesomeIcon icon={faDownload} />
                                    <span>Confirm & Install</span>
                                </Button>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </ServerContentBlock>
    );
};
