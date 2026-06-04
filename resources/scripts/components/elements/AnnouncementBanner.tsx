import React, { useState, useEffect } from 'react';
import { useStoreState } from '@/state';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faInfoCircle, faExclamationTriangle, faExclamationCircle, faTimes } from '@fortawesome/free-solid-svg-icons';
import tw from 'twin.macro';

export default () => {
    const announcement = useStoreState((state) => state.settings.data?.theme);
    const [visible, setVisible] = useState(true);

    const isEnabled = !!announcement?.announcement_enabled;
    const text = announcement?.announcement_text || '';
    const type = announcement?.announcement_type || 'info';
    const isDismissible = !!announcement?.announcement_dismissible;

    useEffect(() => {
        if (text) {
            const dismissed = localStorage.getItem('dismissed_announcement');
            if (dismissed === text) {
                setVisible(false);
            } else {
                setVisible(true);
            }
        }
    }, [text]);

    if (!isEnabled || !text || !visible) {
        return null;
    }

    const handleDismiss = () => {
        localStorage.setItem('dismissed_announcement', text);
        setVisible(false);
    };

    // Style configs based on announcement type
    let bgStyles = tw`from-purple-950/70 to-purple-900/30 border-purple-800/60 text-purple-200`;
    let icon = faInfoCircle;
    let iconColor = tw`text-purple-400`;

    if (type === 'warning') {
        bgStyles = tw`from-amber-950/70 to-amber-900/30 border-amber-800/60 text-amber-200`;
        icon = faExclamationTriangle;
        iconColor = tw`text-amber-400`;
    } else if (type === 'critical') {
        bgStyles = tw`from-red-950/80 to-red-900/40 border-red-800/60 text-red-200`;
        icon = faExclamationCircle;
        iconColor = tw`text-red-400`;
    }

    return (
        <div css={tw`px-6 pt-6`}>
            <div 
                css={[
                    tw`relative flex items-start sm:items-center justify-between p-4 rounded-2xl border bg-gradient-to-r shadow-lg transition-all duration-300`,
                    bgStyles
                ]}
            >
                <div css={tw`flex items-start sm:items-center space-x-3 pr-8`}>
                    <FontAwesomeIcon icon={icon} css={[tw`w-5 h-5 flex-shrink-0 mt-0.5 sm:mt-0`, iconColor]} />
                    <div 
                        css={tw`text-sm font-medium leading-relaxed break-words`} 
                        dangerouslySetInnerHTML={{ __html: text }} 
                    />
                </div>
                {isDismissible && (
                    <button
                        onClick={handleDismiss}
                        css={tw`absolute right-4 top-4 sm:static flex-shrink-0 text-neutral-400 hover:text-white transition-colors duration-150 p-1 rounded-lg hover:bg-neutral-800/50 cursor-pointer`}
                        title="Dismiss"
                    >
                        <FontAwesomeIcon icon={faTimes} css={tw`w-4 h-4`} />
                    </button>
                )}
            </div>
        </div>
    );
};
