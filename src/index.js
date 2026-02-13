/**
 * House Products Carousel — Block Registration
 *
 * Registers the block on the client side using block.json metadata.
 */

import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import save from './save';
import metadata from './block.json';

import './style.scss';
import './editor.scss';

registerBlockType( metadata.name, {
	edit: Edit,
	save,
} );
