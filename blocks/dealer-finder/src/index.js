/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import metadata from '../block.json';

registerBlockType(metadata.name, {
  edit: ({ attributes, setAttributes }) => {
    const blockProps = useBlockProps({
      className: 'michi-dealer-finder'
    });

    return (
      <>
        <InspectorControls>
          <PanelBody title={__('Display Settings', 'michi-dealers')}>
            <ToggleControl
              label={__('Show State Sidebar', 'michi-dealers')}
              checked={attributes.showSidebar}
              onChange={(showSidebar) => setAttributes({ showSidebar })}
            />
          </PanelBody>
        </InspectorControls>

        <div {...blockProps}>
          <div className="dealer-finder-filters">
            <div className="filter-group">
              <label>CHOOSE A COUNTRY</label>
              <select disabled>
                <option>Select a country</option>
              </select>
            </div>
          </div>

          <div className="dealer-preview">
            <p style={{ padding: '2rem', textAlign: 'center', color: '#666' }}>
              {__('Dealer listings will appear here on the frontend.', 'michi-dealers')}
            </p>
          </div>
        </div>
      </>
    );
  },

  save: ({ attributes }) => {
    const blockProps = useBlockProps.save({
      className: 'michi-dealer-finder'
    });

    return (
      <div {...blockProps}>
        <div className="dealer-finder-filters">
          <div className="filter-group">
            <label>CHOOSE A COUNTRY</label>
            <select id="country-select">
              <option value="">Select a country</option>
            </select>
          </div>
        </div>

        <div className="dealer-finder-content">
          {attributes.showSidebar && (
            <aside className="dealer-states-sidebar">
              <h3>STATES/REGIONS</h3>
              <ul id="states-list"></ul>
            </aside>
          )}
          <div className="dealer-results">
            <div id="dealer-listings"></div>
          </div>
        </div>
      </div>
    );
  },
});
