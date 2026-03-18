/**
 * House Categories Carousel — Block Registration
 */

import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';

// Reuse existing styles.
import '../carousel/style.scss';
import '../carousel/editor.scss';

// Add category-specific styles.
import './style.scss';

registerBlockType( metadata.name, {
    edit: Edit,
    save: () => null, // Dynamic block
} );
