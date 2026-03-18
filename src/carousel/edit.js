/**
 * House Products Carousel — Editor Component
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { Placeholder, Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import InspectorFields from '../common/InspectorFields';

export default function Edit({ attributes, setAttributes }) {
    const {
        productsCount,
        columns,
        category,
        excludeCategories,
        autoplay,
        orderBy,
    } = attributes;

    const blockProps = useBlockProps({
        className: 'hpc-editor-wrapper',
    });

    // Fetch products for preview.
    const products = useSelect(
        (select) => {
            const { getEntityRecords } = select('core');
            const query = {
                per_page: productsCount,
                status: 'publish',
                orderby: orderBy === 'menu_order' ? 'menu_order' : orderBy,
                order: 'desc',
                _embed: true, // Embed media and other related data.
            };
            if (category > 0) {
                query.product_cat = category;
            }
            if (excludeCategories && excludeCategories.length > 0) {
                query.product_cat_exclude = excludeCategories;
            }
            return getEntityRecords('postType', 'product', query);
        },
        [productsCount, category, excludeCategories, orderBy]
    );

    return (
        <div {...blockProps}>
            <InspectorControls>
                <InspectorFields
                    attributes={attributes}
                    setAttributes={setAttributes}
                    isCarousel={true}
                />
            </InspectorControls>

            <div className="hpc-editor-preview">
                <div className="hpc-editor-preview__header">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2" />
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />
                    </svg>
                    <span>{__('House Products Carousel', 'house-products-carousel')}</span>
                </div>

                {products === null ? (
                    <div className="hpc-editor-preview__loading">
                        <Spinner />
                        <span>{__('Loading products…', 'house-products-carousel')}</span>
                    </div>
                ) : products.length > 0 ? (
                    <div className="hpc-editor-preview__grid">
                        {products.map((product) => {
                            const featuredMedia = product._embedded?.['wp:featuredmedia']?.[0];
                            const imageUrl = featuredMedia?.media_details?.sizes?.medium_large?.source_url || featuredMedia?.source_url;

                            return (
                                <div key={product.id} className="hpc-editor-preview__card">
                                    {imageUrl ? (
                                        <div className="hpc-editor-preview__card-img">
                                            <img src={imageUrl} alt={decodeEntities(product.title.rendered)} loading="lazy" />
                                        </div>
                                    ) : (
                                        <div className="hpc-editor-preview__card-img hpc-editor-preview__card-img--placeholder">
                                            <span>{__('No Image', 'house-products-carousel')}</span>
                                        </div>
                                    )}
                                    <div className="hpc-editor-preview__card-body">
                                        <h4 className="hpc-editor-preview__card-title">
                                            {decodeEntities(product.title.rendered)}
                                        </h4>
                                        <div className="hpc-editor-preview__card-meta">
                                            {__('Carousel Layout', 'house-products-carousel')}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                ) : (
                    <Placeholder
                        icon="store"
                        label={__('House Products Carousel', 'house-products-carousel')}
                        instructions={__('No products found. Please check your settings.', 'house-products-carousel')}
                    />
                )}

                <div className="hpc-editor-preview__info">
                    <span>
                        {productsCount} {__('products', 'house-products-carousel')} · {columns} {__('columns', 'house-products-carousel')} {autoplay ? ' · ' + __('Autoplay', 'house-products-carousel') : ''}
                    </span>
                </div>
            </div>
        </div>
    );
}
