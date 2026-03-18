/**
 * Shared Inspector Fields for House Products blocks.
 */

import { __ } from '@wordpress/i18n';
import {
    PanelBody,
    RangeControl,
    ToggleControl,
    SelectControl,
    FormTokenField,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';

/* global hpcEditorData */

export default function InspectorFields({ attributes, setAttributes, isCarousel = false }) {
    const {
        productsCount,
        columns,
        category,
        excludeCategories,
        autoplay,
        showArrows,
        showRating,
        orderBy,
    } = attributes;

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

    // Localized fallback.
    const editorCategories = useMemo(() => {
        return typeof hpcEditorData !== 'undefined' &&
            Array.isArray(hpcEditorData.categories)
            ? hpcEditorData.categories
            : [];
    }, []);

    // Build category options for single select.
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

    // Build map for Multi-select mapping (Name <-> ID).
    const categoryMap = useMemo(() => {
        const map = {};
        if (productCategories && productCategories.length > 0) {
            productCategories.forEach((cat) => {
                map[decodeEntities(cat.name)] = cat.id;
                map[cat.id] = decodeEntities(cat.name);
            });
        } else if (editorCategories.length > 0) {
            editorCategories.forEach((cat) => {
                map[decodeEntities(cat.label)] = cat.value;
                map[cat.value] = decodeEntities(cat.label);
            });
        }
        return map;
    }, [productCategories, editorCategories]);

    const suggestions = useMemo(() => {
        return Object.keys(categoryMap).filter(key => isNaN(key));
    }, [categoryMap]);

    const selectedTokens = useMemo(() => {
        return (excludeCategories || []).map(id => categoryMap[id] || id);
    }, [excludeCategories, categoryMap]);

    const onTokenChange = (tokens) => {
        const ids = tokens.map(token => {
            const id = categoryMap[token];
            return id ? id : token;
        });
        setAttributes({ excludeCategories: ids });
    };

    return (
        <>
            <PanelBody
                title={__(
                    'Query Settings',
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
                        'Include Category',
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
                <FormTokenField
                    label={__(
                        'Exclude Categories',
                        'house-products-carousel'
                    )}
                    value={selectedTokens}
                    suggestions={suggestions}
                    onChange={onTokenChange}
                />
                <p style={{ fontSize: '12px', marginTop: '-8px', opacity: 0.7 }}>
                    {__(
                        'Category "hidecat" is excluded by default.',
                        'house-products-carousel'
                    )}
                </p>
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
                        {
                            value: 'popularity',
                            label: __(
                                'Popularity',
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
                {isCarousel && (
                    <>
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
                    </>
                )}
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
        </>
    );
}
