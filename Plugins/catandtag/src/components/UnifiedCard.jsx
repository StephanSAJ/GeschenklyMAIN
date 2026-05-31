import React, { useEffect, useState, useMemo, useCallback, useRef } from 'react';
import {
  Heart,
  ThumbsUp,
  ThumbsDown,
  Sliders,
  X,
  ChevronRight,
  Gift,
  Loader2,
  Package,
  ShoppingCart,
  Tag
} from 'lucide-react';

// Utility Funktion für Debouncing
const debounce = (func, wait) => {
  let timeout;
  return (...args) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => func(...args), wait);
  };
};

// Alert Komponente
const Alert = React.memo(({ variant = 'error', children }) => {
  const styles = useMemo(
    () => ({
      bgColor: variant === 'error' ? 'bg-red-100' : 'bg-teal-100',
      textColor: variant === 'error' ? 'text-red-900' : 'text-teal-900'
    }),
    [variant]
  );
  return (
    <div className={`${styles.bgColor} ${styles.textColor} p-4 rounded-lg border border-current/20 shadow-sm my-6 animate-fade-in min-h-[80px]`}>
      {children}
    </div>
  );
});

// ProductCard Komponente
const ProductCard = React.memo(({ product, onLike, onDislike, likedProducts = [] }) => {
  const imageUrl = useMemo(() => {
    if (!product.image) return '/placeholder-gift.jpg';
    if (typeof product.image === 'string') {
      if (product.image.includes('src=')) {
        const match = product.image.match(/src="([^"]+)"/);
        return match ? match[1] : '/placeholder-gift.jpg';
      }
      if (product.image.startsWith('http')) return product.image;
      return product.image;
    }
    return '/placeholder-gift.jpg';
  }, [product.image]);

  const handleImageError = useCallback((e) => {
    e.currentTarget.src = '/placeholder-gift.jpg';
  }, []);

  const shopButtonText = product.add_to_cart_text || 'Zum Shop ➜';

  return (
    <div className="bg-white rounded-xl shadow-sm overflow-hidden group transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 min-h-[400px]">
      <div className="relative aspect-[4/3]">
        <a href={product.url} className="block focus:outline-none">
          <img
            src={imageUrl}
            alt={product.name}
            width={400}
            height={300}
            loading="lazy"
            decoding="async"
            className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
            onError={handleImageError}
          />
          <div className="absolute inset-0 bg-gradient-to-t from-gray-900/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        </a>
        {onLike && (
          <button
            className="absolute top-3 right-3 p-2 bg-white/95 backdrop-blur-sm rounded-full opacity-0 group-hover:opacity-100 transition-all duration-300 hover:bg-rose-100 shadow-sm focus:outline-none"
            aria-label="Zur Wunschliste hinzufügen"
            onClick={() => onLike(product.id)}
          >
            <Heart className="w-5 h-5 text-rose-500" />
          </button>
        )}
      </div>
      <div className="p-5 flex flex-col flex-grow">
        <h3 className="text-lg font-semibold mb-3 text-gray-800 line-clamp-2 min-h-[3rem]">{product.name}</h3>
        <div className="flex justify-between items-center gap-2 flex-wrap mt-auto">
          <a
            href={product.url}
            className="flex items-center text-teal-600 hover:text-teal-700 font-medium transition-colors duration-200 focus:outline-none"
          >
            Details
            <ChevronRight className="w-4 h-4 ml-1" />
          </a>
          {(onLike || onDislike) && (
            <div className="flex items-center gap-2">
              {onLike && (
                <button
                  className="p-2 rounded-full hover:bg-teal-50 transition-colors duration-200 focus:outline-none"
                  onClick={() => onLike(product.id)}
                >
                  <ThumbsUp
                    className={`w-5 h-5 ${likedProducts.includes(product.id) ? 'text-teal-600' : 'text-gray-400'}`}
                  />
                </button>
              )}
              {onDislike && (
                <button
                  className="p-2 rounded-full hover:bg-rose-50 transition-colors duration-200 focus:outline-none"
                  onClick={() => onDislike(product.id)}
                >
                  <ThumbsDown className="w-5 h-5 text-gray-400" />
                </button>
              )}
            </div>
          )}
          <a
            rel="nofollow"
            target="_blank"
            onClick={() => window._paq?.push(['trackEvent', 'Conversion', 'Klick zum Shop', 'Teaser'])}
            href={`/redirect.php?product=${product.slug}`}
            className="px-4 py-2 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-full hover:from-teal-600 hover:to-teal-700 hover:shadow-lg transition-all duration-300 focus:outline-none"
          >
            <span className="text-sm font-medium">{shopButtonText}</span>
          </a>
        </div>
      </div>
    </div>
  );
});

// PreviewCard Komponente
const PreviewCard = React.memo(({ title, subtitle, icon: Icon, onClick }) => {
  return (
    <button
      onClick={onClick}
      className="bg-white rounded-xl shadow-sm p-6 hover:shadow-lg transition-all duration-300 cursor-pointer border border-gray-200 w-full text-left h-full flex flex-col focus:outline-none items-center min-h-[200px]"
    >
      <div className="flex items-center mb-4 w-full">
        <div className="bg-teal-100 p-2 rounded-full mr-3 shrink-0">
          <Icon className="w-6 h-6 text-teal-600" />
        </div>
        <div className="flex-1 min-w-0">
          <h3 className="text-xl font-semibold text-gray-800">{title}</h3>
          <p className="text-gray-600 text-sm">{subtitle}</p>
        </div>
      </div>
      <div className="mt-auto">
        <span className="inline-block px-4 py-2 bg-teal-600 text-white rounded-full hover:bg-teal-700 transition-all duration-300 focus:outline-none text-sm font-medium">
          Jetzt entdecken
        </span>
      </div>
    </button>
  );
});

// ProductGrid Komponente
const ProductGrid = React.memo(
  ({ products, loading, error, onLike, onDislike, likedProducts, loadMore, hasMore, showMore, setShowMore }) => {
    const observer = useRef();
    const lastProductRef = useCallback(
      (node) => {
        if (loading || !hasMore) return;
        if (observer.current) observer.current.disconnect();
        observer.current = new IntersectionObserver(
          (entries) => {
            if (entries[0].isIntersecting) loadMore();
          },
          { rootMargin: '100px' }
        );
        if (node) observer.current.observe(node);
      },
      [loading, hasMore, loadMore]
    );

    if (loading && !products.length) {
      return (
        <div className="flex flex-col items-center py-16 min-h-[400px]">
          <Loader2 className="w-10 h-10 text-teal-600 animate-spin" />
          <span className="mt-4 text-gray-700 text-lg font-medium">Geschenke werden geladen...</span>
        </div>
      );
    }
    if (error) return <Alert>{error}</Alert>;
    if (!products.length)
      return (
        <div className="text-center py-16 min-h-[400px]">
          <Package className="w-16 h-16 text-gray-300 mx-auto mb-4" />
          <p className="text-gray-600 text-lg font-medium">Keine Geschenke gefunden</p>
        </div>
      );

    const initialProducts = products.slice(0, 8);

    return (
      <div className="flex flex-col items-center min-h-[400px]">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-6 w-full">
          {(showMore ? products : initialProducts).map((p, index) => (
            <div
              key={p.id}
              ref={index === (showMore ? products.length : initialProducts.length) - 1 ? lastProductRef : null}
            >
              <ProductCard
                product={p}
                onLike={onLike}
                onDislike={onDislike}
                likedProducts={likedProducts}
              />
            </div>
          ))}
        </div>
        {!showMore && products.length > 8 && setShowMore && (
          <div className="mt-10">
            <button
              onClick={() => setShowMore(true)}
              className="px-6 py-3 bg-teal-600 text-white rounded-full hover:bg-teal-700 transition-all duration-300 focus:outline-none text-lg font-medium"
            >
              Weitere Geschenke anzeigen
            </button>
          </div>
        )}
        {loading && showMore && (
          <div className="py-8">
            <Loader2 className="w-8 h-8 text-teal-600 animate-spin" />
          </div>
        )}
      </div>
    );
  }
);

// FilterSection Komponente
const FilterSection = React.memo(({ isOpen, onToggle, activeFilters, onToggleFilterValue, onApplyFilters }) => {
  const filterOptions = useMemo(
    () => ({
      pa_alter: {
        title: 'Alter',
        icon: <Tag className="w-5 h-5 mr-3 text-teal-600" />,
        options: ['0-3', '4-6', '6-10', '18-25', '25-40', '40-60']
      },
      pa_beziehung: {
        title: 'Für wen',
        icon: <Heart className="w-5 h-5 mr-3 text-rose-500" />,
        options: ['Familie', 'Freund/in', 'Kollege/in', 'Partner/in']
      },
      pa_eigenschaften: {
        title: 'Eigenschaften',
        icon: <Gift className="w-5 h-5 mr-3 text-amber-500" />,
        options: ['Luxuriös', 'Nachhaltig', 'Individuell', 'Praktisch', 'Kreativ']
      },
      pa_geschlecht: {
        title: 'Geschlecht',
        icon: <Tag className="w-5 h-5 mr-3 text-teal-600" />,
        options: ['Männlich', 'Weiblich', 'Unisex']
      },
      pa_lebensphase: {
        title: 'Lebensphase',
        icon: <Tag className="w-5 h-5 mr-3 text-teal-600" />,
        options: ['Kindheit', 'Jugend', 'Erwachsenenalter', 'Senioren']
      }
    }),
    []
  );

  return (
    <div className="mb-16">
      {isOpen && (
        <div className="bg-white p-6 rounded-xl shadow-md border border-gray-200 min-h-[300px]">
          <h2 className="text-2xl font-bold mb-6 text-gray-900 bg-gradient-to-r from-teal-600 to-amber-500 bg-clip-text text-transparent">
            Deine Auswahl
          </h2>
          {Object.entries(filterOptions).map(([filterKey, filter]) => (
            <div key={filterKey} className="mb-6">
              <h3 className="text-lg font-semibold mb-3 text-gray-800 flex items-center">
                {filter.icon}
                {filter.title}
              </h3>
              <div className="flex flex-wrap gap-3">
                {filter.options.map((option) => {
                  const isActive = activeFilters[filterKey]?.includes(option);
                  return (
                    <button
                      key={option}
                      onClick={() => onToggleFilterValue(filterKey, option)}
                      className={`px-4 py-2 text-sm font-medium rounded-full transition-all duration-300 shadow-sm focus:outline-none ${
                        isActive
                          ? 'bg-teal-600 text-white border border-teal-600'
                          : 'bg-teal-50 text-teal-800 border border-teal-200 hover:bg-teal-100 hover:shadow-sm'
                      }`}
                    >
                      {option}
                    </button>
                  );
                })}
              </div>
            </div>
          ))}
          <div className="flex justify-center mt-6">
            <button
              onClick={onApplyFilters}
              className="flex items-center px-8 py-3 bg-gradient-to-r from-teal-600 to-teal-500 text-white font-semibold rounded-full shadow-md hover:from-teal-700 hover:to-teal-600 transition-all duration-300 focus:outline-none"
            >
              <Sliders className="w-5 h-5 mr-2" />
              Filter anwenden
            </button>
          </div>
        </div>
      )}
    </div>
  );
});

// SectionHeader Komponente
const SectionHeader = React.memo(({ title, subtitle, icon: Icon }) => (
  <div className="flex flex-col items-center mb-10 text-center">
    <div className="inline-flex items-center justify-center p-3 bg-gradient-to-r from-teal-100 to-amber-100 rounded-full mb-4 shadow-sm">
      {Icon && <Icon className="w-7 h-7 text-teal-600" />}
    </div>
    <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-2 bg-gradient-to-r from-teal-600 to-amber-500 bg-clip-text text-transparent">
      {title}
    </h2>
    {subtitle && <p className="text-gray-600 text-lg max-w-2xl">{subtitle}</p>}
    <div className="w-24 h-1 bg-gradient-to-r from-teal-500 to-amber-400 rounded-full mt-4"></div>
  </div>
));

// PartnerShopCard Komponente
const PartnerShopCard = React.memo(({ shop }) => (
  <a
    href={shop.url}
    target="_blank"
    rel="noopener noreferrer"
    className="bg-white rounded-xl shadow-sm overflow-hidden group transition-all duration-300 hover:shadow-lg hover:-translate-y-1 flex flex-col h-full border border-gray-200 focus:outline-none min-h-[300px]"
  >
    <div className="relative h-40 bg-gradient-to-b from-teal-50 to-white overflow-hidden">
      {shop.logo_url ? (
        <img
          src={shop.logo_url}
          alt={shop.name}
          width={160}
          height={160}
          className="w-full h-full object-contain p-6 transition-transform duration-300 group-hover:scale-105"
          loading="lazy"
        />
      ) : (
        <div className="w-full h-full flex items-center justify-center bg-teal-50">
          <ShoppingCart className="w-14 h-14 text-teal-300" />
        </div>
      )}
    </div>
    <div className="p-5 flex-grow">
      <h3 className="text-lg font-semibold mb-2 text-gray-800">{shop.name}</h3>
      <div className="mt-auto pt-4">
        <span className="inline-flex items-center text-teal-600 font-medium group-hover:text-teal-700 transition-colors duration-200">
          Zum Shop
          <ChevronRight className="w-5 h-5 ml-1 transition-transform group-hover:translate-x-1" />
        </span>
      </div>
    </div>
  </a>
));

// SubcategoryCard Komponente
const SubcategoryCard = React.memo(({ subcategory }) => (
  <a
    href={subcategory.link}
    className="bg-white rounded-xl shadow-sm p-5 hover:shadow-lg transition-all duration-300 hover:-translate-y-1 flex flex-col items-center text-center group border border-gray-200 focus:outline-none min-h-[250px]"
  >
    {subcategory.image ? (
      <div
        className="w-20 h-20 bg-cover bg-center rounded-full mb-4 border-2 border-gray-200 group-hover:border-teal-200 transition-colors duration-200"
        style={{ backgroundImage: `url(${subcategory.image})` }}
      />
    ) : (
      <div className="w-20 h-20 bg-teal-50 rounded-full mb-4 flex items-center justify-center">
        <Gift className="w-10 h-10 text-teal-400" />
      </div>
    )}
    <h3 className="text-lg font-semibold text-gray-800 mb-2 group-hover:text-teal-600 transition-colors duration-200">
      {subcategory.name}
    </h3>
    {subcategory.description && (
      <p className="text-sm text-gray-600 line-clamp-2">{subcategory.description.replace(/(<([^>]+)>)/gi, '')}</p>
    )}
  </a>
));

// ContentHeader Komponente
const ContentHeader = React.memo(({ data, mode }) => {
  const image = mode === 'category' ? data.categoryImage : data.tagImage;
  const title = mode === 'category' ? data.categoryTitle : data.tagTitle;
  return (
    <div className="relative overflow-hidden bg-gradient-to-r from-teal-600 via-teal-500 to-amber-400 rounded-xl shadow-md p-8 mb-12 min-h-[200px]">
      <div className="absolute inset-0 opacity-10">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <pattern id="giftPattern" patternUnits="userSpaceOnUse" width="100" height="100" patternTransform="rotate(10)">
              <g opacity="0.8">
                <rect x="15" y="25" width="30" height="30" rx="2" fill="white" />
                <rect x="27.5" y="15" width="5" height="50" fill="white" />
                <rect x="10" y="37.5" width="40" height="5" fill="white" />
                <circle cx="30" cy="15" r="5" fill="white" />
              </g>
              <g opacity="0.6" transform="translate(60, 60)">
                <rect x="10" y="15" width="20" height="20" rx="2" fill="white" />
                <rect x="17.5" y="10" width="3" height="30" fill="white" />
                <rect x="5" y="23.5" width="30" height="3" fill="white" />
                <circle cx="19" cy="10" r="3" fill="white" />
              </g>
              <g opacity="0.7" transform="translate(10, 65)">
                <rect x="10" y="15" width="25" height="25" rx="2" fill="white" />
                <rect x="20" y="10" width="4" height="35" fill="white" />
                <rect x="5" y="25" width="35" height="4" fill="white" />
                <circle cx="20" cy="10" r="4" fill="white" />
              </g>
            </pattern>
          </defs>
          <rect width="100%" height="100%" fill="url(#giftPattern)" />
        </svg>
      </div>
      <div className="relative z-10 flex flex-col md:flex-row items-center">
        {image ? (
          <img
            src={image}
            alt={title}
            width={112}
            height={112}
            className="w-28 h-28 object-cover rounded-full mr-6 border-4 border-white/40 shadow-md transition-transform duration-300 hover:scale-105"
            loading="lazy"
          />
        ) : (
          <div className="w-28 h-28 bg-white/20 backdrop-blur-lg rounded-full mr-6 flex items-center justify-center border-4 border-white/40 shadow-md">
            <Gift className="w-12 h-12 text-white" />
          </div>
        )}
        <div className="text-center md:text-left mt-4 md:mt-0">
          <h1 className="text-4xl md:text-5xl font-bold text-white drop-shadow-md">{title}</h1>
          {data.customHeaderText && (
            <p
              className="text-white/90 mt-3 text-lg md:text-xl max-w-2xl"
              dangerouslySetInnerHTML={{ __html: data.customHeaderText }}
            />
          )}
        </div>
      </div>
    </div>
  );
});

// ContentFooterDetails Komponente
const ContentFooterDetails = React.memo(({ data }) => (
  <footer className="mt-16">
    {data.partnerShops?.length > 0 && (
      <div className="mb-16">
        <SectionHeader title="Unsere Partner" subtitle="Entdecke die besten Shops für deine Geschenke" icon={ShoppingCart} />
        <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
          {data.partnerShops.map((shop, index) => (
            <PartnerShopCard key={index} shop={shop} />
          ))}
        </div>
      </div>
    )}
    {data.subcategories?.length > 0 && (
      <div className="mb-16">
        <SectionHeader title="Mehr Inspiration" subtitle="Finde weitere tolle Geschenkideen" icon={Gift} />
        <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
          {data.subcategories.map((cat) => (
            <SubcategoryCard key={cat.id} subcategory={cat} />
          ))}
        </div>
      </div>
    )}
    {data.customDescription && (
      <div className="mb-16">
        <SectionHeader title="Gut zu wissen" icon={Tag} />
        <div className="bg-white p-6 rounded-xl shadow-md border border-gray-200">
          <div className="prose prose-teal max-w-none text-gray-700" dangerouslySetInnerHTML={{ __html: data.customDescription }} />
        </div>
      </div>
    )}
    <div id="footer-copyright-group" className="min-h-[100px] bg-gray-100 p-4 rounded-lg text-center text-gray-600">
      {/* Platzhalter für den Footer-Inhalt; anpassen nach tatsächlichem Inhalt */}
      <p>© 2025 Dein Unternehmen. Alle Rechte vorbehalten.</p>
    </div>
  </footer>
));

// Haupt-Komponente: UnifiedCard
const UnifiedCard = ({ mode = 'category' }) => {
  const contentIdRef = useRef(null);
  const [state, setState] = useState({
    contentId: null,
    products: {
      popular: { items: [], page: 1, hasMore: true, loading: false, error: null, loaded: false },
      new: { items: [], page: 1, hasMore: true, loading: false, error: null, loaded: false, showMore: false },
      random: { items: [], page: 1, hasMore: true, loading: false, error: null, loaded: false },
      rising: { items: [], page: 1, hasMore: true, loading: false, error: null, loaded: false },
      sixMonths: { items: [], page: 1, hasMore: false, loading: false, error: null, loaded: false },
      filtered: { items: [], page: 1, hasMore: true, loading: false, error: null, loaded: false }
    },
    filterSectionOpen: false,
    activeFilters: {},
    likedProducts: typeof window !== 'undefined' ? JSON.parse(localStorage.getItem('likedProducts') || '[]') : [],
    dislikedProducts: [],
    contentDetails: null,
    showRandomModule: false,
    activeSection: null
  });

  const [infiniteScrollCount, setInfiniteScrollCount] = useState(0);
  const [showInfiniteScrollOverlay, setShowInfiniteScrollOverlay] = useState(false);
  const [infiniteScrollDisabled, setInfiniteScrollDisabled] = useState(false);

  const stateRef = useRef(state);
  useEffect(() => {
    stateRef.current = state;
  }, [state]);

  const idKey = mode === 'category' ? 'category_id' : 'tag_id';
  const randomAction = mode === 'category' ? 'wcc_get_random_products_from_category' : 'wcc_get_random_products_from_tag';
  const detailsAction = mode === 'category' ? 'wcc_get_category_details' : 'wcc_get_tag_details';
  const perPage = 12;

  useEffect(() => {
    if (!contentIdRef.current) {
      const elementId = mode === 'category' ? 'category-card' : 'tag-card';
      const dataAttr = mode === 'category' ? 'data-category-id' : 'data-tag-id';
      const el = document.getElementById(elementId);
      if (el) {
        const id = parseInt(el.getAttribute(dataAttr), 10);
        if (id) {
          contentIdRef.current = id;
          setState((prev) => ({ ...prev, contentId: id }));
        }
      }
    }
  }, [mode]);

  const ajaxFetch = useCallback(async (action, data = {}) => {
    const formData = new FormData();
    formData.append('action', action);
    if (window.wcc_ajax?.nonce) formData.append('nonce', window.wcc_ajax.nonce);
    Object.entries(data).forEach(([key, value]) => formData.append(key, value));
    try {
      const response = await fetch(window.wcc_ajax.ajax_url, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: { 'Cache-Control': 'no-cache' }
      });
      const json = await response.json();
      if (!json.success) throw new Error(json.data || 'Unbekannter Fehler');
      return json.data;
    } catch (err) {
      console.error(`Error in AJAX fetch for ${action}:`, err);
      throw err;
    }
  }, []);

  const loadProducts = useCallback(
    async (type, append = false) => {
      const config = {
        popular: { action: 'wcc_load_more_popular_products' },
        new: { action: 'wcc_load_more_new_products' },
        rising: { action: 'wcc_load_more_rising_products' },
        sixMonths: { action: 'wcc_load_more_six_months_popular_products' },
        filtered: { action: 'wcc_filter_products' },
        random: { action: randomAction }
      };
      const current = stateRef.current.products[type];
      if (!config[type] || current.loading) return;
      if (!append && current.loaded) return;
      if (append && !current.hasMore) return;
      setState((prev) => ({
        ...prev,
        products: {
          ...prev.products,
          [type]: { ...prev.products[type], loading: true, error: null }
        }
      }));
      try {
        const offset = append ? (current.page - 1) * perPage : 0;
        const params = {
          [idKey]: contentIdRef.current,
          limit: type === 'sixMonths' ? 4 : perPage,
          offset,
          ...(type === 'filtered' && { filters: JSON.stringify(state.activeFilters) }),
          ...(type === 'random' && {
            count: perPage,
            exclude: [...state.likedProducts, ...state.dislikedProducts].join(',')
          })
        };
        const data = await ajaxFetch(config[type].action, params);
        setState((prev) => {
          const currentState = prev.products[type];
          const updatedItems = append ? [...currentState.items, ...data] : data;
          return {
            ...prev,
            products: {
              ...prev.products,
              [type]: {
                ...currentState,
                items: updatedItems,
                page: append ? currentState.page + 1 : 2,
                hasMore: data.length === (type === 'sixMonths' ? 4 : perPage),
                loading: false,
                loaded: true
              }
            }
          };
        });
      } catch (err) {
        setState((prev) => ({
          ...prev,
          products: {
            ...prev.products,
            [type]: { ...prev.products[type], error: err.message, loading: false }
          }
        }));
      }
    },
    [state.activeFilters, state.likedProducts, state.dislikedProducts, ajaxFetch, idKey, randomAction]
  );

  const loadRandomProducts = useCallback(() => loadProducts('random', false), [loadProducts]);

  const handleRemoveRandomProduct = useCallback(
    async (productId) => {
      try {
        await ajaxFetch('wcc_record_product_feedback', {
          product_id: productId,
          [idKey]: contentIdRef.current,
          feedback_type: 'dislike'
        });
        const remainingProducts = state.products.random.items.filter((p) => p.id !== productId);
        const newData = await ajaxFetch(randomAction, {
          [idKey]: contentIdRef.current,
          count: perPage,
          exclude: [...state.likedProducts, ...state.dislikedProducts, ...remainingProducts.map((p) => p.id)].join(',')
        });
        setState((prev) => ({
          ...prev,
          products: {
            ...prev.products,
            random: { ...prev.products.random, items: [...remainingProducts, ...(newData || [])] }
          },
          dislikedProducts: [...prev.dislikedProducts, productId]
        }));
      } catch (err) {
        console.error(`Fehler beim Entfernen des Produkts: ${err.message}`);
      }
    },
    [state.products.random, state.likedProducts, state.dislikedProducts, ajaxFetch, randomAction, idKey]
  );

  const handleLikeProduct = useCallback((productId) => {
    setState((prev) => {
      const newLiked = prev.likedProducts.includes(productId)
        ? prev.likedProducts.filter((id) => id !== productId)
        : [...prev.likedProducts, productId];
      localStorage.setItem('likedProducts', JSON.stringify(newLiked));
      return { ...prev, likedProducts: newLiked };
    });
  }, []);

  const toggleFilterValue = useCallback((filterName, value) => {
    setState((prev) => {
      const currentValues = prev.activeFilters[filterName] || [];
      const newValues = currentValues.includes(value)
        ? currentValues.filter((v) => v !== value)
        : [...currentValues, value];
      return {
        ...prev,
        activeFilters: { ...prev.activeFilters, [filterName]: newValues.length ? newValues : undefined },
        products: { ...prev.products, filtered: { ...prev.products.filtered, items: [], page: 1, hasMore: true } }
      };
    });
  }, []);

  const applyFilters = useCallback(
    debounce(() => {
      loadProducts('filtered', false);
    }, 300),
    [loadProducts]
  );

  const loadContentDetails = useCallback(async () => {
    try {
      const data = await ajaxFetch(detailsAction, { [idKey]: contentIdRef.current });
      setState((prev) => ({ ...prev, contentDetails: data }));
    } catch (err) {
      console.error('Fehler beim Laden der Details:', err);
    }
  }, [ajaxFetch, detailsAction, idKey]);

  const handleSectionClick = useCallback(
    (section) => {
      setState((prev) => ({ ...prev, activeSection: section }));
      if (!state.products[section].loaded) {
        loadProducts(section);
      }
    },
    [state.products, loadProducts]
  );

  const handleShowMoreNew = useCallback((value) => {
    setState((prev) => ({
      ...prev,
      products: { ...prev.products, new: { ...prev.products.new, showMore: value } }
    }));
    if (value && infiniteScrollDisabled) {
      setInfiniteScrollDisabled(false);
      setInfiniteScrollCount(0);
    }
  }, [infiniteScrollDisabled]);

  const loadMoreNew = useCallback(() => {
    if (infiniteScrollDisabled) return;
    setInfiniteScrollCount((count) => {
      const newCount = count + 1;
      if (newCount >= 3) {
        setShowInfiniteScrollOverlay(true);
      }
      return newCount;
    });
    loadProducts('new', true);
  }, [infiniteScrollDisabled, loadProducts]);

  const handleOverlayClick = useCallback(() => {
    setInfiniteScrollDisabled(true);
    setShowInfiniteScrollOverlay(false);
    const filterSection = document.getElementById('filterHint');
    if (filterSection) {
      filterSection.scrollIntoView({ behavior: 'smooth' });
    }
  }, []);

  useEffect(() => {
    if (!state.contentId) return;
    loadContentDetails();
    loadProducts('new', false);
  }, [state.contentId, loadContentDetails, loadProducts]);

  useEffect(() => {
    if (state.showRandomModule && !state.products.random.items.length) loadRandomProducts();
  }, [state.showRandomModule, loadRandomProducts]);

  const sections = useMemo(
    () =>
      [
        { key: 'popular', title: 'Aktuell beliebt', subtitle: 'Die Favoriten unserer Community', icon: Heart },
        mode === 'category' && { key: 'rising', title: 'Aufsteiger', subtitle: 'Heute besonders gefragt', icon: ThumbsUp },
        { key: 'sixMonths', title: 'Zeitlose Klassiker', subtitle: 'Immer eine gute Wahl', icon: Tag }
      ].filter(Boolean),
    [mode]
  );

  return (
    <div
      className="min-h-screen bg-gradient-to-b from-teal-50 via-white to-amber-50 relative"
      id={mode === 'category' ? 'category-card' : 'tag-card'}
      {...(mode === 'category'
        ? { 'data-category-id': state.contentId }
        : { 'data-tag-id': state.contentId })}
    >
      <main className="container mx-auto max-w-screen-xl px-4 py-12 pb-32">
        {state.contentDetails && <ContentHeader data={state.contentDetails} mode={mode} />}

        <section className="mb-16">
          <SectionHeader title="Frische Ideen" subtitle="Neueste Geschenk-Inspirationen" icon={Gift} />
          <ProductGrid
            products={state.products.new.items}
            loading={state.products.new.loading}
            error={state.products.new.error}
            likedProducts={state.likedProducts}
            loadMore={loadMoreNew}
            hasMore={state.products.new.hasMore}
            showMore={state.products.new.showMore}
            setShowMore={handleShowMoreNew}
          />
        </section>

        <section id="filterHint" className="mb-16">
          <div className="bg-teal-100 p-6 rounded-xl shadow-sm border border-teal-200 flex flex-col items-center min-h-[120px]">
            <p className="text-teal-900 text-lg font-medium mb-4">
              Finde genau das Richtige mit unseren Filtern!
            </p>
            <button
              onClick={() => setState((prev) => ({ ...prev, filterSectionOpen: !prev.filterSectionOpen }))}
              className="inline-flex items-center px-6 py-3 bg-teal-600 text-white font-semibold rounded-full shadow-md hover:bg-teal-700 transition-all duration-300 focus:outline-none"
            >
              <Sliders className="w-5 h-5 mr-2" />
              {state.filterSectionOpen ? 'Filter schließen' : 'Filter öffnen'}
            </button>
          </div>
        </section>

        <FilterSection
          isOpen={state.filterSectionOpen}
          onToggle={() => setState((prev) => ({ ...prev, filterSectionOpen: !prev.filterSectionOpen }))}
          activeFilters={state.activeFilters}
          onToggleFilterValue={toggleFilterValue}
          onApplyFilters={applyFilters}
        />

        {state.activeFilters && Object.keys(state.activeFilters).length > 0 && (
          <section className="mb-16" id="filteredProducts">
            <SectionHeader title="Deine Auswahl" subtitle="Geschenke nach deinen Wünschen" icon={Sliders} />
            <ProductGrid
              products={state.products.filtered.items}
              loading={state.products.filtered.loading}
              error={state.products.filtered.error}
              likedProducts={state.likedProducts}
              loadMore={() => loadProducts('filtered', true)}
              hasMore={state.products.filtered.hasMore}
              showMore={true}
            />
          </section>
        )}

        <section className="mb-16">
          <SectionHeader title="Entdecke mehr" subtitle="Beliebte Kategorien auf einen Blick" icon={Gift} />
          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            {sections.map(({ key, title, subtitle, icon }) => (
              <PreviewCard key={key} title={title} subtitle={subtitle} icon={icon} onClick={() => handleSectionClick(key)} />
            ))}
          </div>
        </section>

        {state.activeSection && (
          <section className="mb-16" id={state.activeSection}>
            <SectionHeader
              title={sections.find((s) => s.key === state.activeSection).title}
              subtitle={sections.find((s) => s.key === state.activeSection).subtitle}
              icon={sections.find((s) => s.key === state.activeSection).icon}
            />
            <ProductGrid
              products={state.products[state.activeSection].items}
              loading={state.products[state.activeSection].loading}
              error={state.products[state.activeSection].error}
              likedProducts={state.likedProducts}
              loadMore={() => loadProducts(state.activeSection, true)}
              hasMore={state.products[state.activeSection].hasMore}
              showMore={true}
            />
          </section>
        )}

        <section className="mb-16">
          <SectionHeader title="Lass dich überraschen" subtitle="Entdecke zufällige Geschenkideen" icon={Gift} />
          <div className="flex justify-center mb-6">
            <button
              onClick={() => setState((prev) => ({ ...prev, showRandomModule: !prev.showRandomModule }))}
              className="px-6 py-3 bg-teal-600 text-white rounded-full hover:bg-teal-700 transition-all duration-300 focus:outline-none text-lg font-medium"
            >
              {state.showRandomModule ? 'Zufallsmodul schließen' : 'Zufällige Produkte anzeigen'}
            </button>
          </div>
          {state.showRandomModule && (
            <ProductGrid
              products={state.products.random.items}
              loading={state.products.random.loading}
              error={state.products.random.error}
              onDislike={handleRemoveRandomProduct}
              onLike={handleLikeProduct}
              likedProducts={state.likedProducts}
              loadMore={() => loadProducts('random', true)}
              hasMore={state.products.random.hasMore}
              showMore={true}
            />
          )}
        </section>

        {state.contentDetails && <ContentFooterDetails data={state.contentDetails} />}
      </main>

      {showInfiniteScrollOverlay && (
        <div
          className="fixed bottom-4 right-[30%] bg-teal-600 text-white p-4 rounded shadow-lg cursor-pointer z-50"
          onClick={handleOverlayClick}
        >
          Filter und Top Produkte
        </div>
      )}
    </div>
  );
};

export default UnifiedCard;
