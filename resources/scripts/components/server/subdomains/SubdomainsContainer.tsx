import React, { useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import useFlash from '@/plugins/useFlash';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import FlashMessageRender from '@/components/FlashMessageRender';
import http from '@/api/http';
import tw from 'twin.macro';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faGlobe, faTrashAlt, faPlus, faInfoCircle } from '@fortawesome/free-solid-svg-icons';
import Button from '@/components/elements/Button';
import Input from '@/components/elements/Input';
import Label from '@/components/elements/Label';
import Select from '@/components/elements/Select';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import GreyRowBox from '@/components/elements/GreyRowBox';
import Spinner from '@/components/elements/Spinner';

interface Subdomain {
    id: number;
    subdomain: string;
    domain: string;
    record_type: string;
    ip: string;
    port: number;
    created_at: string;
}

export default () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const { clearAndAddHttpError, clearFlashes, addFlash } = useFlash();

    const [loading, setLoading] = useState(true);
    const [subdomains, setSubdomains] = useState<Subdomain[]>([]);
    const [allowedDomains, setAllowedDomains] = useState<string[]>([]);
    const [defaultIp, setDefaultIp] = useState('');
    const [defaultPort, setDefaultPort] = useState<number>(25565);
    const [subdomainLimit, setSubdomainLimit] = useState<number>(0);

    const [subdomain, setSubdomain] = useState('');
    const [domain, setDomain] = useState('');
    const [recordType, setRecordType] = useState('SRV');
    const [isSubmit, setIsSubmit] = useState(false);

    const loadData = () => {
        setLoading(true);
        http.get(`/api/client/servers/${uuid}/subdomains`)
            .then(({ data }) => {
                setSubdomains(data.subdomains);
                setAllowedDomains(data.allowed_domains);
                setDefaultIp(data.default_ip);
                setDefaultPort(data.default_port);
                setSubdomainLimit(data.subdomain_limit);
                if (data.allowed_domains.length > 0) {
                    setDomain(data.allowed_domains[0]);
                }
            })
            .catch((err) => {
                console.error(err);
                clearAndAddHttpError({ key: 'subdomains', error: err });
            })
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        clearFlashes('subdomains');
        loadData();
    }, []);

    const onCreate = (e: React.FormEvent) => {
        e.preventDefault();
        clearFlashes('subdomains');
        setIsSubmit(true);

        http.post(`/api/client/servers/${uuid}/subdomains`, {
            subdomain,
            domain,
            record_type: recordType,
        })
            .then(({ data }) => {
                setSubdomain('');
                addFlash({
                    key: 'subdomains',
                    type: 'success',
                    message: `Subdomain ${data.subdomain.subdomain}.${data.subdomain.domain} has been successfully created! ${
                        data.dns_synced ? '(DNS Synced)' : '(DNS Local/Mock Sync completed)'
                    }`,
                });
                loadData();
            })
            .catch((err) => {
                console.error(err);
                clearAndAddHttpError({ key: 'subdomains', error: err });
            })
            .finally(() => setIsSubmit(false));
    };

    const onDelete = (id: number) => {
        clearFlashes('subdomains');
        setLoading(true);

        http.delete(`/api/client/servers/${uuid}/subdomains/${id}`)
            .then(() => {
                addFlash({
                    key: 'subdomains',
                    type: 'success',
                    message: 'Subdomain has been successfully deleted!',
                });
                loadData();
            })
            .catch((err) => {
                console.error(err);
                clearAndAddHttpError({ key: 'subdomains', error: err });
                setLoading(false);
            });
    };

    return (
        <ServerContentBlock title={'Subdomains'}>
            <FlashMessageRender byKey={'subdomains'} css={tw`mb-4`} />
            
            {loading && subdomains.length === 0 ? (
                <Spinner size={'large'} centered />
            ) : (
                <div css={tw`grid grid-cols-1 lg:grid-cols-3 gap-6`}>
                    {/* Create Form */}
                    <div css={tw`lg:col-span-1 bg-neutral-900 border border-neutral-800 rounded-lg p-6 relative h-auto`}>
                        <SpinnerOverlay visible={isSubmit} />
                        <h2 css={tw`text-lg font-header font-semibold text-gray-100 mb-4`}>
                            Create Subdomain
                        </h2>
                        
                        {allowedDomains.length === 0 ? (
                            <div css={tw`bg-yellow-500 bg-opacity-10 border border-yellow-500 border-opacity-20 text-yellow-300 text-sm p-4 rounded-lg flex items-start space-x-3`}>
                                <FontAwesomeIcon icon={faInfoCircle} css={tw`mt-1 flex-shrink-0`} />
                                <span>No domains are configured by the admin yet. Please contact support.</span>
                            </div>
                        ) : subdomainLimit > 0 && subdomains.length >= subdomainLimit ? (
                            <div css={tw`bg-yellow-500 bg-opacity-10 border border-yellow-500 border-opacity-20 text-yellow-300 text-sm p-4 rounded-lg flex items-start space-x-3`}>
                                <FontAwesomeIcon icon={faInfoCircle} css={tw`mt-1 flex-shrink-0`} />
                                <span>You have reached the maximum allowed limit of {subdomainLimit} subdomains for this server.</span>
                            </div>
                        ) : (
                            <form onSubmit={onCreate} css={tw`space-y-4`}>
                                <div>
                                    <Label>Subdomain Prefix</Label>
                                    <div css={tw`flex items-stretch`}>
                                        <Input
                                            type={'text'}
                                            placeholder={'play'}
                                            value={subdomain}
                                            onChange={(e) => setSubdomain(e.target.value)}
                                            required
                                            css={tw`rounded-r-none`}
                                        />
                                        <div css={tw`bg-neutral-800 border border-l-0 border-neutral-700 px-3 flex items-center text-sm text-neutral-400 rounded-r-md whitespace-nowrap select-none`}>
                                            .{domain}
                                        </div>
                                    </div>
                                    <p css={tw`text-xs text-neutral-500 mt-1`}>
                                        Only alphanumeric characters and hyphens are allowed.
                                    </p>
                                </div>

                                <div>
                                    <Label>Root Domain</Label>
                                    <Select
                                        value={domain}
                                        onChange={(e) => setDomain(e.target.value)}
                                        required
                                    >
                                        {allowedDomains.map((d) => (
                                            <option key={d} value={d}>
                                                {d}
                                            </option>
                                        ))}
                                    </Select>
                                </div>

                                <div>
                                    <Label>Record Type</Label>
                                    <Select
                                        value={recordType}
                                        onChange={(e) => setRecordType(e.target.value)}
                                        required
                                    >
                                        <option value="SRV">SRV (Recommended for Minecraft)</option>
                                        <option value="A">A Record (For Web/Python/etc.)</option>
                                        <option value="CNAME">CNAME (For Alias)</option>
                                    </Select>
                                </div>

                                <div css={tw`bg-purple-500 bg-opacity-10 border border-purple-500 border-opacity-20 text-purple-300 text-xs p-4 rounded-lg space-y-1`}>
                                    <div css={tw`font-bold flex items-center space-x-2`}>
                                        <FontAwesomeIcon icon={faInfoCircle} />
                                        <span>Auto-Fetched Connection Details</span>
                                    </div>
                                    <div>Target IP: <span css={tw`font-mono text-gray-100`}>{defaultIp}</span></div>
                                    <div>Target Port: <span css={tw`font-mono text-gray-100`}>{defaultPort}</span></div>
                                    <div css={tw`pt-1 text-neutral-400`}>
                                        This subdomain will automatically map to your server&apos;s allocated port and IP.
                                    </div>
                                </div>

                                <Button type={'submit'} css={tw`w-full flex justify-center items-center`}>
                                    <FontAwesomeIcon icon={faPlus} css={tw`mr-2`} />
                                    <span>Create Subdomain</span>
                                </Button>
                            </form>
                        )}
                    </div>

                    {/* Subdomains Table */}
                    <div css={tw`lg:col-span-2 space-y-3`}>
                        <h2 css={tw`text-lg font-header font-semibold text-gray-100`}>
                            Configured Subdomains {subdomainLimit > 0 ? `(${subdomains.length}/${subdomainLimit})` : ''}
                        </h2>
                        
                        {subdomains.length === 0 ? (
                            <div css={tw`bg-neutral-900 border border-neutral-800 rounded-lg p-8 text-center text-neutral-400`}>
                                <FontAwesomeIcon icon={faGlobe} size={'2x'} css={tw`text-neutral-600 mb-2`} />
                                <p>No subdomains have been created for this server yet.</p>
                            </div>
                        ) : (
                            <div css={tw`space-y-3`}>
                                {subdomains.map((sub) => (
                                    <GreyRowBox key={sub.id} css={tw`flex items-center justify-between`}>
                                        <div css={tw`flex items-center space-x-4`}>
                                            <div css={tw`w-10 h-10 rounded-lg bg-purple-500 bg-opacity-10 flex items-center justify-center text-purple-400 flex-shrink-0`}>
                                                <FontAwesomeIcon icon={faGlobe} />
                                            </div>
                                            <div>
                                                <div css={tw`font-bold text-gray-100 text-sm md:text-base`}>
                                                    {sub.subdomain}.{sub.domain}
                                                </div>
                                                <div css={tw`text-xs text-neutral-400 flex flex-wrap gap-x-3 mt-0.5`}>
                                                    <span>Type: <strong css={tw`text-purple-400 font-mono`}>{sub.record_type}</strong></span>
                                                    <span>Target: <strong css={tw`text-gray-300 font-mono`}>{sub.ip}:{sub.port}</strong></span>
                                                </div>
                                            </div>
                                        </div>
                                        <Button
                                            isRed
                                            onClick={() => onDelete(sub.id)}
                                            css={tw`p-2 px-3 flex items-center space-x-1.5`}
                                        >
                                            <FontAwesomeIcon icon={faTrashAlt} />
                                            <span css={tw`hidden md:inline`}>Delete</span>
                                        </Button>
                                    </GreyRowBox>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            )}
        </ServerContentBlock>
    );
};
