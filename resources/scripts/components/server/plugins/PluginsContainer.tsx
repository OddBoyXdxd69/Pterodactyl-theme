import React, { useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import useFlash from '@/plugins/useFlash';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import FlashMessageRender from '@/components/FlashMessageRender';
import http from '@/api/http';
import tw from 'twin.macro';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPuzzlePiece, faSearch, faDownload, faCheckCircle, faTimesCircle, faArrowRight } from '@fortawesome/free-solid-svg-icons';
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

export default () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const { clearAndAddHttpError, clearFlashes, addFlash } = useFlash();

    const [loading, setLoading] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [source, setSource] = useState<'modrinth' | 'spiget' | 'hangar'>('modrinth');
    const [plugins, setPlugins] = useState<PluginItem[]>([]);
    const [installingId, setInstallingId] = useState<string | null>(null);

    const executeSearch = (query: string = '', currentSource: 'modrinth' | 'spiget' | 'hangar' = source) => {
        setLoading(true);
        clearFlashes('plugins');

        if (currentSource === 'modrinth') {
            const url = `https://api.modrinth.com/v2/search?query=${encodeURIComponent(query)}&facets=[["categories:spigot","categories:paper","categories:purpur","categories:bungeecord","categories:velocity","categories:waterfall","categories:folia"]]&index=relevance`;
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
            // Spiget doesn't support empty search well, default to a general search if query is empty
            const searchQueryStr = query.trim() || 'essentials';
            const url = `https://api.spiget.org/v2/search/resources/${encodeURIComponent(searchQueryStr)}?size=25&fields=id,name,tag,downloads,likes`;
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
            const url = `https://hangar.papermc.io/api/v1/projects?q=${encodeURIComponent(query)}&limit=25`;
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
        executeSearch('', 'modrinth');
    }, []);

    const onSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        executeSearch(searchQuery);
    };

    const handleSourceChange = (newSource: 'modrinth' | 'spiget' | 'hangar') => {
        setSource(newSource);
        executeSearch(searchQuery, newSource);
    };

    const installPlugin = (plugin: PluginItem) => {
        setInstallingId(plugin.id);
        clearFlashes('plugins');

        const downloadAndPull = (downloadUrl: string, finalFilename: string) => {
            http.post(`/api/client/servers/${uuid}/files/pull`, {
                url: downloadUrl,
                directory: 'plugins',
                filename: finalFilename,
            })
                .then(() => {
                    addFlash({
                        key: 'plugins',
                        type: 'success',
                        message: `Successfully installed "${plugin.name}" (${finalFilename}) into the plugins directory! Restart your server to load it.`,
                    });
                })
                .catch((err) => {
                    console.error(err);
                    clearAndAddHttpError({ key: 'plugins', error: err });
                })
                .finally(() => setInstallingId(null));
        };

        if (plugin.source === 'modrinth') {
            // Fetch project versions
            fetch(`https://api.modrinth.com/v2/project/${plugin.id}/version`)
                .then((res) => res.json())
                .then((versions) => {
                    if (!versions || versions.length === 0) {
                        throw new Error('No versions found for this plugin on Modrinth.');
                    }
                    const latest = versions[0];
                    const file = latest.files.find((f: any) => f.primary) || latest.files[0];
                    if (!file) {
                        throw new Error('No downloadable file found for this version.');
                    }
                    downloadAndPull(file.url, file.filename);
                })
                .catch((err) => {
                    console.error(err);
                    addFlash({ key: 'plugins', type: 'danger', message: err.message || 'Failed to install plugin from Modrinth.' });
                    setInstallingId(null);
                });
        } else if (plugin.source === 'spiget') {
            const downloadUrl = `https://api.spiget.org/v2/resources/${plugin.id}/download`;
            const cleanName = plugin.name.replace(/[^a-zA-Z0-9]/g, '_').toLowerCase();
            downloadAndPull(downloadUrl, `${cleanName}.jar`);
        } else if (plugin.source === 'hangar') {
            // Fetch Hangar versions
            fetch(`https://hangar.papermc.io/api/v1/projects/${plugin.owner}/${plugin.slug}/versions?limit=1`)
                .then((res) => res.json())
                .then((data) => {
                    const versions = data.result || [];
                    if (versions.length === 0) {
                        throw new Error('No versions found for this plugin on Hangar.');
                    }
                    const latest = versions[0];
                    const versionName = latest.name;
                    let downloadUrl = '';
                    
                    if (latest.downloads.PAPER_DOWNLOAD) {
                        downloadUrl = `https://hangar.papermc.io/api/v1/projects/${plugin.owner}/${plugin.slug}/versions/${versionName}/downloads`;
                    } else if (latest.downloads.EXTERNAL) {
                        downloadUrl = latest.downloads.EXTERNAL;
                    } else {
                        throw new Error('No download URL found for the latest version on Hangar.');
                    }

                    downloadAndPull(downloadUrl, `${plugin.slug}-${versionName}.jar`);
                })
                .catch((err) => {
                    console.error(err);
                    addFlash({ key: 'plugins', type: 'danger', message: err.message || 'Failed to install plugin from Hangar.' });
                    setInstallingId(null);
                });
        }
    };

    return (
        <ServerContentBlock title={'Minecraft Plugins Downloader'}>
            <FlashMessageRender byKey={'plugins'} css={tw`mb-4`} />

            <div css={tw`flex flex-col space-y-6`}>
                {/* Search Header Bar */}
                <form onSubmit={onSearchSubmit} css={tw`bg-neutral-900 border border-neutral-800 rounded-lg p-5 flex flex-col md:flex-row gap-4 items-center`}>
                    <div css={tw`flex-1 w-full`}>
                        <Input
                            placeholder={'Search for plugins... (e.g. EssentialsX, WorldEdit, LuckPerms)'}
                            type={'text'}
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                        />
                    </div>
                    <div css={tw`w-full md:w-48`}>
                        <Select
                            value={source}
                            onChange={(e) => handleSourceChange(e.target.value as any)}
                        >
                            <option value="modrinth">Modrinth API</option>
                            <option value="spiget">SpigotMC (Spiget)</option>
                            <option value="hangar">Hangar (PaperMC)</option>
                        </Select>
                    </div>
                    <Button type={'submit'} css={tw`w-full md:w-36 flex justify-center items-center`}>
                        <FontAwesomeIcon icon={faSearch} css={tw`mr-2`} />
                        <span>Search</span>
                    </Button>
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
                            <p css={tw`text-sm text-neutral-500 mt-1`}>Try searching for something else or changing the search source.</p>
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
                                        {installingId === plugin.id ? (
                                            <Button disabled css={tw`p-2 px-4 flex items-center space-x-2`}>
                                                <Spinner size={'small'} />
                                                <span>Installing...</span>
                                            </Button>
                                        ) : (
                                            <Button
                                                onClick={() => installPlugin(plugin)}
                                                css={tw`p-2 px-4 flex items-center space-x-2`}
                                            >
                                                <FontAwesomeIcon icon={faDownload} />
                                                <span>Install</span>
                                            </Button>
                                        )}
                                    </div>
                                </GreyRowBox>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </ServerContentBlock>
    );
};
