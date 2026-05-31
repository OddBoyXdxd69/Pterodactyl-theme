import React, { useContext, useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import { Form, Formik, FormikHelpers } from 'formik';
import Field from '@/components/elements/Field';
import { object, string } from 'yup';
import http from '@/api/http';
import tw from 'twin.macro';
import { Button } from '@/components/elements/button/index';
import { useFlashKey } from '@/plugins/useFlash';
import useFileManagerSwr from '@/plugins/useFileManagerSwr';
import { WithClassname } from '@/components/types';
import FlashMessageRender from '@/components/FlashMessageRender';
import { Dialog, DialogWrapperContext } from '@/components/elements/dialog';
import asDialog from '@/hoc/asDialog';

interface Values {
    url: string;
    filename: string;
}

const schema = object().shape({
    url: string().url('A valid URL must be provided.').required('A URL is required.'),
    filename: string().nullable(),
});

const PullFileDialog = asDialog({
    title: 'Pull File From URL',
})(() => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const directory = ServerContext.useStoreState((state) => state.files.directory);

    const { mutate } = useFileManagerSwr();
    const { close } = useContext(DialogWrapperContext);
    const { clearAndAddHttpError } = useFlashKey('files:pull-modal');

    useEffect(() => {
        return () => {
            clearAndAddHttpError();
        };
    }, []);

    const submit = ({ url, filename }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        http.post(`/api/client/servers/${uuid}/files/pull`, {
            url,
            directory,
            filename: filename || undefined,
        })
            .then(() => {
                mutate();
                close();
            })
            .catch((error) => {
                setSubmitting(false);
                clearAndAddHttpError(error);
            });
    };

    return (
        <Formik onSubmit={submit} validationSchema={schema} initialValues={{ url: '', filename: '' }}>
            {({ submitForm }) => (
                <>
                    <FlashMessageRender key={'files:pull-modal'} />
                    <Form css={tw`m-0 space-y-4`}>
                        <Field
                            autoFocus
                            id={'url'}
                            name={'url'}
                            label={'File URL'}
                            placeholder={'https://example.com/file.zip'}
                        />
                        <Field
                            id={'filename'}
                            name={'filename'}
                            label={'Filename (Optional)'}
                            placeholder={'Leave empty to auto-detect'}
                        />
                    </Form>
                    <Dialog.Footer>
                        <Button.Text className={'w-full sm:w-auto'} onClick={close}>
                            Cancel
                        </Button.Text>
                        <Button className={'w-full sm:w-auto'} onClick={submitForm}>
                            Pull File
                        </Button>
                    </Dialog.Footer>
                </>
            )}
        </Formik>
    );
});

export default ({ className }: WithClassname) => {
    const [open, setOpen] = useState(false);

    return (
        <>
            <PullFileDialog open={open} onClose={setOpen.bind(this, false)} />
            <Button onClick={setOpen.bind(this, true)} className={className}>
                Pull File
            </Button>
        </>
    );
};
