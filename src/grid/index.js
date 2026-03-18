/**
 * House Products Grid — Block Registration
 */

import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';

// We can reuse the carousel styles or extract common ones.
// For now, let's import the carousel style if we want the card design to look same.
import '../carousel/style.scss';
import '../carousel/editor.scss';

// Grid specific layout styles.
import './style.scss';

registerBlockType( metadata.name, {
    edit: Edit,
    save: () => null, // Dynamic block
} );
