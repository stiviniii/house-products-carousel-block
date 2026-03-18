/**
 * House Categories Carousel — Editor Component
 */

import { __ } from "@wordpress/i18n";
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import {
    PanelBody,
    RangeControl,
    ToggleControl,
    FormTokenField,
    Placeholder,
    Spinner,
} from "@wordpress/components";
import { useSelect } from "@wordpress/data";
import { useMemo } from "@wordpress/element";
import { decodeEntities } from "@wordpress/html-entities";

export default function Edit({ attributes, setAttributes }) {
    const {
        categoryIds,
        columns,
        showProductCount,
        autoplay,
        showArrows,
        enableAnimation,
        animationDuration,
        animationStagger,
        trackOverflowVisible,
    } = attributes;

    const blockProps = useBlockProps({
        className: "hpc-editor-wrapper",
    });

    // Fetch all product categories for selection.
    const allCategories = useSelect((select) => {
        const { getEntityRecords } = select("core");
        return getEntityRecords("taxonomy", "product_cat", {
            per_page: 100,
            hide_empty: false,
        });
    }, []);

    // Localized fallback from PHP for "instant" load.
    /* global hpcEditorData */
    const categoriesRoot = useMemo(() => {
        // Prioritize localized data since it includes proper image URLs
        if (
            typeof hpcEditorData !== "undefined" &&
            Array.isArray(hpcEditorData.categories)
        ) {
            return hpcEditorData.categories.map((cat) => ({
                id: cat.value,
                name: decodeEntities(cat.label),
                count: cat.count,
                image: cat.image || "",
            }));
        }

        // Fallback to REST API data (but images likely won't be available)
        if (allCategories && allCategories.length > 0) {
            return allCategories.map((cat) => {
                // Try to get image from various possible sources in the REST API response
                let imageUrl = "";
                if (cat.image?.src) {
                    imageUrl = cat.image.src;
                } else if (cat.image) {
                    imageUrl = cat.image;
                } else if (cat.meta && cat.meta.thumbnail_id) {
                    // If thumbnail_id is available, we'd need to resolve it to URL
                    // For now, fall back to localized data
                    imageUrl = "";
                }

                return {
                    id: cat.id,
                    name: decodeEntities(cat.name),
                    count: cat.count,
                    image: imageUrl,
                };
            });
        }

        return null;
    }, [allCategories]);

    // Map for Multi-select mapping (Name <-> ID).
    const categoryMap = useMemo(() => {
        const map = {};
        if (categoriesRoot) {
            categoriesRoot.forEach((cat) => {
                map[cat.name] = cat.id;
                map[cat.id] = cat.name;
            });
        }
        return map;
    }, [categoriesRoot]);

    const suggestions = useMemo(() => {
        return Object.keys(categoryMap).filter((key) => isNaN(key));
    }, [categoryMap]);

    const selectedTokens = useMemo(() => {
        return (categoryIds || []).map((id) => categoryMap[id] || id);
    }, [categoryIds, categoryMap]);

    const onTokenChange = (tokens) => {
        const ids = tokens.map((token) => {
            const id = categoryMap[token];
            return id ? id : token;
        });
        setAttributes({ categoryIds: ids });
    };

    // Filter categories that are actually selected for preview.
    const selectedCategoriesData = useMemo(() => {
        if (!categoriesRoot || !categoryIds.length) return [];
        return categoryIds
            .map((id) => categoriesRoot.find((cat) => cat.id === id))
            .filter(Boolean);
    }, [categoriesRoot, categoryIds]);

    return (
        <div {...blockProps}>
            <InspectorControls>
                <PanelBody title={__("Category Selection", "house-products-carousel")}>
                    <FormTokenField
                        label={__("Select Categories", "house-products-carousel")}
                        value={selectedTokens}
                        suggestions={suggestions}
                        onChange={onTokenChange}
                        help={__(
                            "Type to search and select product categories.",
                            "house-products-carousel",
                        )}
                    />
                </PanelBody>

                <PanelBody title={__("Layout Settings", "house-products-carousel")}>
                    <RangeControl
                        label={__("Columns (Desktop)", "house-products-carousel")}
                        value={columns}
                        onChange={(value) => setAttributes({ columns: value })}
                        min={1}
                        max={6}
                    />
                    <ToggleControl
                        label={__("Show Product Count", "house-products-carousel")}
                        checked={showProductCount}
                        onChange={(value) => setAttributes({ showProductCount: value })}
                    />
                </PanelBody>

                <PanelBody
                    title={__("Carousel Settings", "house-products-carousel")}
                    initialOpen={false}
                >
                    <ToggleControl
                        label={__("Autoplay", "house-products-carousel")}
                        checked={autoplay}
                        onChange={(value) => setAttributes({ autoplay: value })}
                    />
                    <ToggleControl
                        label={__("Show Arrows", "house-products-carousel")}
                        checked={showArrows}
                        onChange={(value) => setAttributes({ showArrows: value })}
                    />
                    <ToggleControl
                        label={__("Overflow Visible", "house-products-carousel")}
                        checked={trackOverflowVisible}
                        onChange={(value) => setAttributes({ trackOverflowVisible: value })}
                    />
                </PanelBody>

                <PanelBody
                    title={__("Animation Settings", "house-products-carousel")}
                    initialOpen={false}
                >
                    <ToggleControl
                        label={__("Enable Animation", "house-products-carousel")}
                        checked={enableAnimation}
                        onChange={(value) => setAttributes({ enableAnimation: value })}
                    />
                    {enableAnimation && (
                        <>
                            <RangeControl
                                label={__("Duration (ms)", "house-products-carousel")}
                                value={animationDuration}
                                onChange={(value) =>
                                    setAttributes({ animationDuration: value })
                                }
                                min={200}
                                max={2000}
                                step={50}
                            />
                            <RangeControl
                                label={__("Stagger Delay (ms)", "house-products-carousel")}
                                value={animationStagger}
                                onChange={(value) => setAttributes({ animationStagger: value })}
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
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                    </svg>
                    <span>{__("Categories Carousel", "house-products-carousel")}</span>
                </div>

                {allCategories === null ? (
                    <div className="hpc-editor-preview__loading">
                        <Spinner />
                        <span>{__("Loading categories…", "house-products-carousel")}</span>
                    </div>
                ) : selectedCategoriesData.length > 0 ? (
                    <div
                        className="hpc-categories-grid"
                        style={{ gridTemplateColumns: `repeat(${columns}, 1fr)` }}
                    >
                        {selectedCategoriesData.map((cat) => (
                            <div key={cat.id} className="hpc-category-preview-card">
                                {cat.image ? (
                                    <img
                                        src={cat.image}
                                        alt={cat.name}
                                        className="hpc-category-preview-card__img"
                                        style={{
                                            width: "100%",
                                            height: "100%",
                                            objectFit: "cover",
                                            position: "absolute",
                                        }}
                                    />
                                ) : (
                                    <div className="hpc-category-preview-card__img">
                                        {__("No Image", "house-products-carousel")}
                                    </div>
                                )}
                                <div className="hpc-category-preview-card__info">
                                    <div className="hpc-category-preview-card__name">
                                        {decodeEntities(cat.name)}
                                    </div>
                                    {showProductCount && (
                                        <div className="hpc-category-preview-card__count">
                                            {cat.count} {__("Products", "house-products-carousel")}
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <Placeholder
                        icon="category"
                        label={__("House Categories Carousel", "house-products-carousel")}
                        instructions={__(
                            "Please select product categories in the block settings.",
                            "house-products-carousel",
                        )}
                    />
                )}
            </div>
        </div>
    );
}
