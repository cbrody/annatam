import React, { Fragment } from 'react';

import Field from 'Modal/field';
import { __wpupg } from 'Shared/Translations';

const SectionOther = (props) => {
    return (
        <Fragment>
            {
                'posts' === props.grid.type
                &&
                <Field
                    value={ props.grid.deeplinking }
                    onChange={ ( value ) => {
                        props.onGridChange({
                            deeplinking: value,
                        });
                    }}
                    type="checkbox"
                    label={ __wpupg( 'Enable deeplinking' ) }
                    help={ __wpupg( 'When deeplinking is enabled the URL will automatically get updated to reflect the position in the grid. Visitors will be able to copy that link to go back to it.' ) }
                />
            }
            <Field
                value={ props.grid.empty_message }
                onChange={ ( value ) => {
                    props.onGridChange({
                        empty_message: value,
                    });
                }}
                type="tinymce"
                label={ __wpupg( 'Empty Message' ) }
                help={ __wpupg( 'Message to show when there are no items to display.' ) }
            />
        </Fragment>
    );
}
export default SectionOther;
