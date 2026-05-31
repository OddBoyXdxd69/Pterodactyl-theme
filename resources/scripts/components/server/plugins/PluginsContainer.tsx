import React, { useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import useFlash from '@/plugins/useFlash';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import FlashMessageRender from '@/components/FlashMessageRender';
import http from '@/api/http';
import tw from 'twin.macro';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPuzzlePiece, faSearch, faDownload, faTimes, faTimesCircle, faInfoCircle, faCalendarAlt, faCodeBranch, faGlobe } from '@fortawesome/free-solid-svg-icons';
import Button from '@/components/elements/Button';
import Input from '@/components/elements/Input';
import Select from '@/components/elements/Select';
import Spinner from '@/components/elements/Spinner';
import GreyRowBox from '@/components/elements/GreyRowBox';

interface PluginItem {
    id: string;
    name: string;
    description: string;
    author: string;
    downloads: number;
    iconUrl: string | null;
    source: 'modrinth' | 'spiget' | 'hangar';
    slug?: string;
    owner?: string; // Hangar specific
}

interface VersionItem {
    id: string;
    name: string;
    versionNumber: string;
    date: string;
    gameVersions: string;
    loaders: string;
    downloadUrl: string;
    filename: string;
}

export default () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const { clearAndAddHttpError, clearFlashes, addFlash } = useFlash();

    const [loading, setLoading] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [source, setSource] = useState<'modrinth' | 'spiget' | 'hangar'>('modrinth');
    
    // Filters
    const [selectedLoader, setSelectedLoader] = useState<string>('all');
    const [gameVersion, setGameVersion] = useState<string>('');

    const [plugins, setPlugins] = useState<PluginItem[]>([]);
    
    // Modal states
    const [showModal, setShowModal] = useState(false);
    const [selectedPlugin, setSelectedPlugin] = useState<PluginItem | null>(null);
    const [versions, setVersions] = useState<VersionItem[]>([]);
    const [visibleCount, setVisibleCount] = useState(20);
    const [loadingVersions, setLoadingVersions] = useState(false);
    const [installingVersionId, setInstallingVersionId] = useState<string | null>(null);

    const executeSearch = (
        query: string = '', 
        currentSource: 'modrinth' | 'spiget' | 'hangar' = source,
        loader: string = selectedLoader,
        version: string = gameVersion
    ) => {
        setLoading(true);
        clearFlashes('plugins');

        const cleanVersion = version.trim();

        if (currentSource === 'modrinth') {
            // Build Modrinth facets
            const facetsArray: string[][] = [];
            
            // Add loader category facet
            if (loader !== 'all') {
                facetsArray.push([`categories:${loader}`]);
            } else {
                // If "all" loaders, we restrict to common server types
                facetsArray.push(["categories:spigot", "categories:paper", "categories:purpur", "categories:bungeecord", "categories:velocity", "categories:waterfall", "categories:folia"]);
            }

            // Add game version facet
            if (cleanVersion) {
                facetsArray.push([`versions:${cleanVersion}`]);
            }

            const facetsParam = encodeURIComponent(JSON.stringify(facetsArray));
            const url = `https://api.modrinth.com/v2/search?query=${encodeURIComponent(query)}&facets=${facetsParam}&index=relevance&limit=24`;

            fetch(url)
                .then((res) => res.json())
                .then((data) => {
                    const mapped: PluginItem[] = (data.hits || []).map((hit: any) => ({
                        id: hit.project_id,
                        name: hit.title,
                        description: hit.description || 'No description provided.',
                        author: hit.author || 'Unknown',
                        downloads: hit.downloads || 0,
                        iconUrl: hit.icon_url || null,
                        source: 'modrinth',
                        slug: hit.slug,
                    }));
                    setPlugins(mapped);
                })
                .catch((err) => {
                    console.error(err);
                    addFlash({ key: 'plugins', type: 'danger', message: 'Failed to search Modrinth. Please try again.' });
                })
                .finally(() => setLoading(false));
        } else if (currentSource === 'spiget') {
            const searchQueryStr = query.trim() || 'essentials';
            const url = `https://api.spiget.org/v2/search/resources/${encodeURIComponent(searchQueryStr)}?size=24&fields=id,name,tag,downloads,likes,file`;
            fetch(url)
                .then((res) => res.json())
                .then((data) => {
                    if (data.error) {
                        setPlugins([]);
                        return;
                    }
                    const mapped: PluginItem[] = (data || []).map((item: any) => ({
                        id: String(item.id),
                        name: item.name,
                        description: item.tag || 'No description provided.',
                        author: 'SpigotMC Author',
                        downloads: item.downloads || 0,
                        iconUrl: item.icon ? `https://static.spigotmc.org/resources/resource-icons/${item.id}.jpg` : null,
                        source: 'spiget',
                    }));
                    setPlugins(mapped);
                })
                .catch((err) => {
                    console.error(err);
                    addFlash({ key: 'plugins', type: 'danger', message: 'Failed to search Spiget (SpigotMC). Please try again.' });
                })
                .finally(() => setLoading(false));
        } else if (currentSource === 'hangar') {
            // Build Hangar query params
            let url = `https://hangar.papermc.io/api/v1/projects?q=${encodeURIComponent(query)}&limit=24`;
            
            // Map common loaders to Hangar platforms
            if (loader !== 'all') {
                let platform = '';
                if (loader === 'paper' || loader === 'spigot' || loader === 'purpur') platform = 'PAPER';
                else if (loader === 'velocity') platform = 'VELOCITY';
                else if (loader === 'bungeecord' || loader === 'waterfall') platform = 'WATERFALL';
                else if (loader === 'folia') platform = 'FOLIA';
                
                if (platform) {
                    url += `&platform=${platform}`;
                }
            }

            if (cleanVersion) {
                url += `&version=${encodeURIComponent(cleanVersion)}`;
            }

            fetch(url)
                .then((res) => res.json())
                .then((data) => {
                    const mapped: PluginItem[] = (data.result || []).map((p: any) => ({
                        id: `${p.namespace.owner}/${p.namespace.slug}`,
                        name: p.name,
                        description: p.description || 'No description provided.',
                        author: p.namespace.owner,
                        downloads: p.stats.downloads || 0,
                        iconUrl: p.avatarUrl || null,
                        source: 'hangar',
                        slug: p.namespace.slug,
                        owner: p.namespace.owner,
                    }));
                    setPlugins(mapped);
                })
                .catch((err) => {
                    console.error(err);
                    addFlash({ key: 'plugins', type: 'danger', message: 'Failed to search Hangar (PaperMC). Please try again.' });
                })
                .finally(() => setLoading(false));
        }
    };

    useEffect(() => {
        executeSearch('', 'modrinth', 'all', '');
    }, []);

    const onSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        executeSearch(searchQuery);
    };

    const handleSourceChange = (newSource: 'modrinth' | 'spiget' | 'hangar') => {
        setSource(newSource);
        executeSearch(searchQuery, newSource);
    };

    const openInstallModal = (plugin: PluginItem) => {
        setSelectedPlugin(plugin);
        setVersions([]);
        setVisibleCount(20);
        setLoadingVersions(true);
        setShowModal(true);

        if (plugin.source === 'modrinth') {
            fetch(`https://api.modrinth.com/v2/project/${plugin.id}/version`)
                .then((res) => res.json())
                .then((data) => {
                    const list: VersionItem[] = (data || []).map((v: any) => {
                        const file = v.files.find((f: any) => f.primary) || v.files[0];
                        return {
                            id: v.id,
                            name: v.name,
                            versionNumber: v.version_number,
                            date: new Date(v.date_published).toLocaleDateString(),
                            gameVersions: v.game_versions.slice(0, 5).join(', ') + (v.game_versions.length > 5 ? '...' : ''),
                            loaders: v.loaders.join(', '),
                            downloadUrl: file?.url || '',
                            filename: file?.filename || `${plugin.slug}-${v.version_number}.jar`,
                        };
                    });
                    setVersions(list);
                })
                .catch((err) => {
                    console.error(err);
                    addFlash({ key: 'plugins', type: 'danger', message: 'Failed to retrieve plugin versions from Modrinth.' });
                })
                .finally(() => setLoadingVersions(false));
        } else if (plugin.source === 'spiget') {
            fetch(`https://api.spiget.org/v2/resources/${plugin.id}/versions?size=100`)
                .then((res) => res.json())
                .then((data) => {
                    if (data.error) {
                        setVersions([]);
                        return;
                    }
                    const list: VersionItem[] = (data || []).map((v: any) => ({
                        id: String(v.id),
                        name: v.name,
                        versionNumber: v.name,
                        date: v.releaseDate ? new Date(v.releaseDate * 1000).toLocaleDateString() : 'Unknown',
                        gameVersions: 'Spigot / Paper Compatible',
                        loaders: 'spigot, paper',
                        downloadUrl: `https://api.spiget.org/v2/resources/${plugin.id}/versions/${v.id}/download`,
                        filename: `${plugin.name.replace(/[^a-zA-Z0-9]/g, '_').toLowerCase()}-${v.name}.jar`,
                    }));
                    setVersions(list);
                })
                .catch((err) => {
                    console.error(err);
                    addFlash({ key: 'plugins', type: 'danger', message: 'Failed to retrieve plugin versions from Spiget.' });
                })
                .finally(() => setLoadingVersions(false));
        } else if (plugin.source === 'hangar') {
            fetch(`https://hangar.papermc.io/api/v1/projects/${plugin.owner}/${plugin.slug}/versions?limit=100`)
                .then((res) => res.json())
                .then((data) => {
                    const list: VersionItem[] = (data.result || []).map((v: any) => {
                        let downloadUrl = '';
                        if (v.downloads.PAPER_DOWNLOAD) {
                            downloadUrl = `https://hangar.papermc.io/api/v1/projects/${plugin.owner}/${plugin.slug}/versions/${v.name}/downloads`;
                        } else if (v.downloads.EXTERNAL) {
                            downloadUrl = v.downloads.EXTERNAL;
                        }
                        
                        const loaders = Object.keys(v.platformDependencies || {}).map(l => l.toLowerCase()).join(', ');
                        const gameVersionsArr = Object.values(v.platformDependencies || {}).flatMap((arr: any) => arr);
                        const gameVersions = gameVersionsArr.slice(0, 5).join(', ') + (gameVersionsArr.length > 5 ? '...' : '');

                        return {
                            id: v.name,
                            name: v.name,
                            versionNumber: v.name,
                            date: new Date(v.createdAt).toLocaleDateString(),
                            gameVersions: gameVersions || 'Compatible',
                            loaders: loaders || 'paper, folia',
                            downloadUrl: downloadUrl,
                            filename: `${plugin.slug}-${v.name}.jar`,
                        };
                    });
                    setVersions(list);
                })
                .catch((err) => {
                    console.error(err);
                    addFlash({ key: 'plugins', type: 'danger', message: 'Failed to retrieve plugin versions from Hangar.' });
                })
                .finally(() => setLoadingVersions(false));
        }
    };

    const triggerDownload = (version: VersionItem) => {
        if (!selectedPlugin) return;
        setInstallingVersionId(version.id);
        clearFlashes('plugins');

        http.post(`/api/client/servers/${uuid}/files/pull`, {
            url: version.downloadUrl,
            directory: 'plugins',
            filename: version.filename,
        })
            .then(() => {
                addFlash({
                    key: 'plugins',
                    type: 'success',
                    message: `Successfully installed "${selectedPlugin.name}" version ${version.versionNumber} (${version.filename})! Restart your server to load it.`,
                });
                setShowModal(false);
            })
            .catch((err) => {
                console.error(err);
                clearAndAddHttpError({ key: 'plugins', error: err });
            })
            .finally(() => setInstallingVersionId(null));
    };

    return (
        <ServerContentBlock title={'Minecraft Plugins Downloader'}>
            <FlashMessageRender byKey={'plugins'} css={tw`mb-4`} />

            <div css={tw`flex flex-col space-y-6`}>
                {/* Advanced Search Options */}
                <form onSubmit={onSearchSubmit} css={tw`bg-neutral-900 border border-neutral-800 rounded-lg p-5 flex flex-col gap-4`}>
                    <div css={tw`flex flex-col md:flex-row gap-4 items-center`}>
                        <div css={tw`flex-1 w-full`}>
                            <Input
                                placeholder={'Search for plugins... (e.g. EssentialsX, WorldEdit, LuckPerms)'}
                                type={'text'}
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                            />
                        </div>
                        <div css={tw`w-full md:w-56`}>
                            <Select
                                value={source}
                                onChange={(e) => handleSourceChange(e.target.value as any)}
                            >
                                <option value="modrinth">Modrinth Repository</option>
                                <option value="spiget">SpigotMC (Spiget)</option>
                                <option value="hangar">Hangar (PaperMC)</option>
                            </Select>
                        </div>
                        <Button type={'submit'} css={tw`w-full md:w-36 flex justify-center items-center`}>
                            <FontAwesomeIcon icon={faSearch} css={tw`mr-2`} />
                            <span>Search</span>
                        </Button>
                    </div>

                    {/* Loader & Game Version Filters */}
                    {source !== 'spiget' && (
                        <div css={tw`grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-neutral-800`}>
                            <div>
                                <label css={tw`text-xs text-neutral-400 font-bold uppercase mb-1 block`}>Server Loader</label>
                                <Select
                                    value={selectedLoader}
                                    onChange={(e) => {
                                        setSelectedLoader(e.target.value);
                                        executeSearch(searchQuery, source, e.target.value, gameVersion);
                                    }}
                                >
                                    <option value="all">All Platforms / Loaders</option>
                                    <option value="paper">Paper</option>
                                    <option value="spigot">Spigot</option>
                                    <option value="purpur">Purpur</option>
                                    <option value="folia">Folia</option>
                                    <option value="bungeecord">BungeeCord</option>
                                    <option value="velocity">Velocity</option>
                                </Select>
                            </div>
                            <div>
                                <label css={tw`text-xs text-neutral-400 font-bold uppercase mb-1 block`}>Minecraft Version</label>
                                <div css={tw`flex gap-2`}>
                                    <Input
                                        placeholder={'e.g. 1.21.2 or 1.20.4'}
                                        type={'text'}
                                        value={gameVersion}
                                        onChange={(e) => setGameVersion(e.target.value)}
                                    />
                                    <Button
                                        type={'button'}
                                        onClick={() => executeSearch(searchQuery, source, selectedLoader, gameVersion)}
                                        css={tw`px-4`}
                                    >
                                        Apply
                                    </Button>
                                </div>
                            </div>
                        </div>
                    )}
                </form>

                {/* Plugins Listing */}
                <div css={tw`space-y-3`}>
                    <h2 css={tw`text-lg font-header font-semibold text-gray-100 flex items-center space-x-2`}>
                        <FontAwesomeIcon icon={faPuzzlePiece} css={tw`text-purple-400`} />
                        <span>Available Plugins</span>
                    </h2>

                    {loading ? (
                        <div css={tw`flex flex-col items-center justify-center p-12 bg-neutral-900 border border-neutral-800 rounded-lg`}>
                            <Spinner size={'large'} />
                            <p css={tw`text-sm text-neutral-400 mt-4`}>Searching plugin repository, please wait...</p>
                        </div>
                    ) : plugins.length === 0 ? (
                        <div css={tw`bg-neutral-900 border border-neutral-800 rounded-lg p-12 text-center text-neutral-400`}>
                            <FontAwesomeIcon icon={faTimesCircle} size={'3x'} css={tw`text-neutral-700 mb-3`} />
                            <p css={tw`text-lg font-semibold text-neutral-200`}>No Plugins Found</p>
                            <p css={tw`text-sm text-neutral-500 mt-1`}>Try searching for something else or changing the filters.</p>
                        </div>
                    ) : (
                        <div css={tw`grid grid-cols-1 md:grid-cols-2 gap-4`}>
                            {plugins.map((plugin) => (
                                <GreyRowBox key={plugin.id} css={tw`flex items-start justify-between p-4 bg-neutral-900 border border-neutral-800 hover:border-purple-500 hover:bg-neutral-900/80 transition-all duration-150 rounded-lg`}>
                                    <div css={tw`flex items-start space-x-4 flex-1 min-w-0 pr-4`}>
                                        {plugin.iconUrl ? (
                                            <img
                                                src={plugin.iconUrl}
                                                alt={plugin.name}
                                                css={tw`w-12 h-12 rounded-lg bg-neutral-800 object-cover flex-shrink-0`}
                                                onError={(e) => {
                                                    e.currentTarget.style.display = 'none';
                                                }}
                                            />
                                        ) : (
                                            <div css={tw`w-12 h-12 rounded-lg bg-purple-500/10 text-purple-400 flex items-center justify-center flex-shrink-0 text-xl font-bold`}>
                                                <FontAwesomeIcon icon={faPuzzlePiece} />
                                            </div>
                                        )}
                                        <div css={tw`flex-1 min-w-0`}>
                                            <div css={tw`font-bold text-gray-100 text-base truncate`} title={plugin.name}>
                                                {plugin.name}
                                            </div>
                                            <p css={tw`text-xs text-neutral-400 mt-1 line-clamp-2 min-h-[32px]`} title={plugin.description}>
                                                {plugin.description}
                                            </p>
                                            <div css={tw`flex items-center space-x-3 mt-2 text-[10px] text-neutral-500`}>
                                                <span>Author: <strong css={tw`text-gray-300`}>{plugin.author}</strong></span>
                                                <span>•</span>
                                                <span>Downloads: <strong css={tw`text-gray-300`}>{plugin.downloads.toLocaleString()}</strong></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div css={tw`flex-shrink-0 self-center`}>
                                        <Button
                                            onClick={() => openInstallModal(plugin)}
                                            css={tw`p-2 px-4 flex items-center space-x-2`}
                                        >
                                            <FontAwesomeIcon icon={faDownload} />
                                            <span>Install</span>
                                        </Button>
                                    </div>
                                </GreyRowBox>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {/* Version Selection Modal Dialog */}
            {showModal && selectedPlugin && (
                <div css={tw`fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm`}>
                    <div css={tw`bg-neutral-900 border border-neutral-800 rounded-lg max-w-2xl w-full p-6 relative max-h-[85vh] flex flex-col`}>
                        {/* Header */}
                        <div css={tw`flex justify-between items-center pb-4 border-b border-neutral-800`}>
                            <h3 css={tw`text-lg font-bold text-gray-100 truncate`}>
                                Select Version: {selectedPlugin.name}
                            </h3>
                            <button onClick={() => setShowModal(false)} css={tw`text-neutral-400 hover:text-white focus:outline-none`}>
                                <FontAwesomeIcon icon={faTimes} size={'lg'} />
                            </button>
                        </div>

                        {/* List */}
                        <div css={tw`flex-1 overflow-y-auto my-4 pr-1 space-y-3`}>
                            {loadingVersions ? (
                                <div css={tw`flex flex-col items-center justify-center py-12`}>
                                    <Spinner size={'large'} />
                                    <p css={tw`text-sm text-neutral-400 mt-4`}>Retrieving versions, please wait...</p>
                                </div>
                            ) : versions.length === 0 ? (
                                <div css={tw`py-12 text-center text-neutral-400`}>
                                    <FontAwesomeIcon icon={faInfoCircle} size={'2x'} css={tw`mb-2 text-neutral-600`} />
                                    <p>No compatible versions found for this plugin.</p>
                                </div>
                            ) : (
                                <>
                                    {versions.slice(0, visibleCount).map((v) => (
                                        <div key={v.id} css={tw`flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-neutral-800 border border-neutral-700/60 rounded-lg gap-4 hover:border-purple-500 transition-colors`}>
                                            <div css={tw`flex-1 space-y-1`}>
                                                <div css={tw`flex items-center space-x-2`}>
                                                    <strong css={tw`text-purple-400 text-sm font-mono`}>{v.versionNumber}</strong>
                                                    <span css={tw`text-[10px] bg-neutral-700 px-2 py-0.5 rounded text-neutral-300 font-semibold truncate max-w-[200px]`} title={v.name}>
                                                        {v.name}
                                                    </span>
                                                </div>
                                                <div css={tw`text-xs text-neutral-400 flex flex-col space-y-0.5`}>
                                                    <span css={tw`flex items-center gap-1.5`}>
                                                        <FontAwesomeIcon icon={faCalendarAlt} css={tw`w-3 text-neutral-500`} />
                                                        Released: {v.date}
                                                    </span>
                                                    <span css={tw`flex items-center gap-1.5`}>
                                                        <FontAwesomeIcon icon={faCodeBranch} css={tw`w-3 text-neutral-500`} />
                                                        Loaders: <strong css={tw`text-gray-300`}>{v.loaders || 'N/A'}</strong>
                                                    </span>
                                                    <span css={tw`flex items-center gap-1.5`}>
                                                        <FontAwesomeIcon icon={faGlobe} css={tw`w-3 text-neutral-500`} />
                                                        Game Versions: <strong css={tw`text-gray-300`}>{v.gameVersions || 'Compatible'}</strong>
                                                    </span>
                                                </div>
                                            </div>
                                            <div css={tw`flex-shrink-0 self-start sm:self-center`}>
                                                {installingVersionId === v.id ? (
                                                    <Button disabled css={tw`p-2 px-4 flex items-center space-x-2`}>
                                                        <Spinner size={'small'} />
                                                        <span>Installing...</span>
                                                    </Button>
                                                ) : (
                                                    <Button
                                                        onClick={() => triggerDownload(v)}
                                                        css={tw`p-2 px-4 flex items-center space-x-2`}
                                                    >
                                                        <FontAwesomeIcon icon={faDownload} />
                                                        <span>Install</span>
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                    ))}

                                    {versions.length > visibleCount && (
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
                </div>
            )}
        </ServerContentBlock>
    );
};
