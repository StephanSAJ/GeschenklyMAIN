import React, { Suspense } from 'react';
import { createRoot } from 'react-dom/client';
import './index.css'; // Tailwind CSS

// Lazy loading der UnifiedCard-Komponente
const UnifiedCard = React.lazy(() => import('./components/UnifiedCard'));

document.addEventListener('DOMContentLoaded', () => {
  let el = document.getElementById('tag-card');
  let mode = 'tag';
  if (!el) {
    el = document.getElementById('category-card');
    mode = 'category';
  }
  if (el) {
    const root = createRoot(el);
    root.render(
      <Suspense fallback={<div>Loading...</div>}>
        <UnifiedCard mode={mode} />
      </Suspense>
    );
  }
});
