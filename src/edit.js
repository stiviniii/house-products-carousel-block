/**
 * House Products Carousel — Editor Component
 *
 * Provides InspectorControls for all block settings and a
 * visual preview placeholder inside the editor.
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
    PanelBody,
    RangeControl,
    ToggleControl,
    SelectControl,
    Placeholder,
    Spinner,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';

/* global hpcEditorData */

export default function Edit({ attributes, setAttributes }) {
    const {
        productsCount,
        columns,
        category,
        autoplay,
        showArrows,
        showRating,
        orderBy,
    } = attributes;

    const blockProps = useBlockProps({
        className: 'hpc-editor-wrapper',
    });

    // Fetch product categories from the REST API via store.
    const productCategories = useSelect((select) => {
        const { getEntityRecords } = select('core');
        return getEntityRecords('taxonomy', 'product_cat', {
            per_page: 100,
            hide_empty: false,
            orderby: 'name',
            order: 'asc',
        });
    }, []);

    // Localized fallback (safely referenced).
    const editorCategories = useMemo(() => {
        return typeof hpcEditorData !== 'undefined' &&
            Array.isArray(hpcEditorData.categories)
            ? hpcEditorData.categories
            : [];
    }, []);

    // Build category options only when data changes.
    const categoryOptions = useMemo(() => {
        const options = [
            {
                value: 0,
                label: __('All Categories', 'house-products-carousel'),
            },
        ];

        if (productCategories && productCategories.length > 0) {
            productCategories.forEach((cat) => {
                options.push({
                    value: cat.id,
                    label: decodeEntities(cat.name),
                });
            });
        } else if (editorCategories.length > 0) {
            editorCategories.forEach((cat) => {
                options.push({
                    value: cat.value,
                    label: decodeEntities(cat.label),
                });
            });
        }

        return options;
    }, [productCategories, editorCategories]);

    // Fetch a preview of products for the editor.
    const products = useSelect(
        (select) => {
            const { getEntityRecords } = select('core');
            const query = {
                per_page: productsCount,
                status: 'publish',
                orderby:
                    orderBy === 'menu_order' ? 'menu_order' : orderBy,
                order: 'desc',
            };
            if (category > 0) {
                query.product_cat = category;
            }
            return getEntityRecords('postType', 'product', query);
        },
        [productsCount, category, orderBy]
    );

    return (
        <div {...blockProps}>
            <InspectorControls>
                <PanelBody
                    title={__(
                        'Carousel Settings',
                        'house-products-carousel'
                    )}
                    initialOpen={true}
                >
                    <RangeControl
                        label={__(
                            'Products Count',
                            'house-products-carousel'
                        )}
                        value={productsCount}
                        onChange={(value) =>
                            setAttributes({ productsCount: value })
                        }
                        min={1}
                        max={24}
                    />
                    <RangeControl
                        label={__(
                            'Columns (Desktop)',
                            'house-products-carousel'
                        )}
                        value={columns}
                        onChange={(value) =>
                            setAttributes({ columns: value })
                        }
                        min={1}
                        max={6}
                    />
                    <SelectControl
                        label={__(
                            'Category',
                            'house-products-carousel'
                        )}
                        value={category}
                        options={categoryOptions}
                        onChange={(value) =>
                            setAttributes({
                                category: parseInt(value, 10),
                            })
                        }
                    />
                    <SelectControl
                        label={__(
                            'Order By',
                            'house-products-carousel'
                        )}
                        value={orderBy}
                        options={[
                            {
                                value: 'date',
                                label: __(
                                    'Date',
                                    'house-products-carousel'
                                ),
                            },
                            {
                                value: 'menu_order',
                                label: __(
                                    'Menu Order',
                                    'house-products-carousel'
                                ),
                            },
                            {
                                value: 'price',
                                label: __(
                                    'Price',
                                    'house-products-carousel'
                                ),
                            },
                        ]}
                        onChange={(value) =>
                            setAttributes({ orderBy: value })
                        }
                    />
                </PanelBody>
                <PanelBody
                    title={__(
                        'Display Options',
                        'house-products-carousel'
                    )}
                    initialOpen={false}
                >
                    <ToggleControl
                        label={__(
                            'Autoplay',
                            'house-products-carousel'
                        )}
                        checked={autoplay}
                        onChange={(value) =>
                            setAttributes({ autoplay: value })
                        }
                    />
                    <ToggleControl
                        label={__(
                            'Show Arrows',
                            'house-products-carousel'
                        )}
                        checked={showArrows}
                        onChange={(value) =>
                            setAttributes({ showArrows: value })
                        }
                    />
                    <ToggleControl
                        label={__(
                            'Show Rating',
                            'house-products-carousel'
                        )}
                        checked={showRating}
                        onChange={(value) =>
                            setAttributes({ showRating: value })
                        }
                    />
                    <ToggleControl
                        label={__(
                            'Overflow Visible (Track)',
                            'house-products-carousel'
                        )}
                        help={__(
                            'Allow slides to spill outside the carousel track.',
                            'house-products-carousel'
                        )}
                        checked={attributes.trackOverflowVisible}
                        onChange={(value) =>
                            setAttributes({
                                trackOverflowVisible: value,
                            })
                        }
                    />
                    {attributes.trackOverflowVisible && (
                        <div
                            style={{
                                color: '#d93025',
                                fontSize: '12px',
                                marginTop: '-10px',
                                marginBottom: '15px',
                                fontStyle: 'italic',
                                fontWeight: '500',
                                padding: '8px',
                                borderLeft: '3px solid #d93025',
                                backgroundColor: '#fce8e6',
                            }}
                        >
                            {__(
                                'Note: To avoid layout bleeding, ensure your section container has "overflow: hidden" applied.',
                                'house-products-carousel'
                            )}
                        </div>
                    )}
                </PanelBody>
                <PanelBody
                    title={__(
                        'Animation Settings',
                        'house-products-carousel'
                    )}
                    initialOpen={false}
                >
                    <ToggleControl
                        label={__(
                            'Enable Reveal Animation',
                            'house-products-carousel'
                        )}
                        checked={attributes.enableAnimation}
                        onChange={(value) =>
                            setAttributes({ enableAnimation: value })
                        }
                    />
                    {attributes.enableAnimation && (
                        <>
                            <RangeControl
                                label={__(
                                    'Duration (ms)',
                                    'house-products-carousel'
                                )}
                                value={attributes.animationDuration}
                                onChange={(value) =>
                                    setAttributes({
                                        animationDuration: value,
                                    })
                                }
                                min={200}
                                max={2000}
                                step={50}
                            />
                            <RangeControl
                                label={__(
                                    'Stagger Delay (ms)',
                                    'house-products-carousel'
                                )}
                                value={attributes.animationStagger}
                                onChange={(value) =>
                                    setAttributes({
                                        animationStagger: value,
                                    })
                                }
                                min={0}
                                max={500}
                                step={10}
                            />
                        </>
                    )}
                </PanelBody>
            </InspectorControls>

            <div className="hpc-editor-preview">
                <div className="hpc-editor-preview__header">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        aria-hidden="true"
                    >
                        <rect
                            x="2"
                            y="7"
                            width="20"
                            height="14"
                            rx="2"
                            ry="2"
                        />
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />
                    </svg>
                    <span>
                        {__(
                            'House Products Carousel',
                            'house-products-carousel'
                        )}
                    </span>
                </div>

                {products === null || products === undefined ? (
                    <div className="hpc-editor-preview__loading">
                        <Spinner />
                        <span>
                            {__(
                                'Loading products…',
                                'house-products-carousel'
                            )}
                        </span>
                    </div>
                ) : products.length > 0 ? (
                    <div className="hpc-editor-preview__grid">
                        {products.map((product) => (
                            <div
                                key={product.id}
                                className="hpc-editor-preview__card"
                            >
                                {product.featured_media ? (
                                    <ProductImage
                                        mediaId={
                                            product.featured_media
                                        }
                                    />
                                ) : (
                                    <div className="hpc-editor-preview__card-img hpc-editor-preview__card-img--placeholder">
                                        <span>
                                            {__(
                                                'No Image',
                                                'house-products-carousel'
                                            )}
                                        </span>
                                    </div>
                                )}
                                <div className="hpc-editor-preview__card-body">
                                    <h4 className="hpc-editor-preview__card-title">
                                        {decodeEntities(
                                            product.title.rendered
                                        )}
                                    </h4>
                                    <div className="hpc-editor-preview__card-meta">
                                        {__(
                                            'Product Card',
                                            'house-products-carousel'
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <Placeholder
                        icon="store"
                        label={__(
                            'House Products Carousel',
                            'house-products-carousel'
                        )}
                        instructions={__(
                            'No products found. Please check your settings or add WooCommerce products.',
                            'house-products-carousel'
                        )}
                    />
                )}

                <div className="hpc-editor-preview__info">
                    <span>
                        {productsCount}{' '}
                        {__(
                            'products',
                            'house-products-carousel'
                        )}{' '}
                        ·{' '}{columns}{' '}
                        {__(
                            'columns',
                            'house-products-carousel'
                        )}
                        {autoplay
                            ? ' · ' +
                            __(
                                'Autoplay',
                                'house-products-carousel'
                            )
                            : ''}
                    </span>
                </div>
            </div>
        </div>
    );
}

/**
 * Fetches and displays a product's featured image in the editor.
 *
 * @param {Object} props           Component props.
 * @param {number} props.mediaId   The attachment/media ID.
 * @return {JSX.Element} Image element or loading spinner.
 */
function ProductImage({ mediaId }) {
    const media = useSelect(
        (select) => select('core').getMedia(mediaId),
        [mediaId]
    );

    if (!media) {
        return (
            <div className="hpc-editor-preview__card-img hpc-editor-preview__card-img--loading">
                <Spinner />
            </div>
        );
    }

    const src =
        media?.media_details?.sizes?.medium?.source_url ||
        media?.source_url;

    return (
        <div className="hpc-editor-preview__card-img">
            <img
                src={src}
                alt={media.alt_text || ''}
                loading="lazy"
            />
        </div>
    );
}
