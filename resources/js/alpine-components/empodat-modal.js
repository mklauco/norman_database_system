export default function empodatModal() {
    return {
        showModal: false,
        record: null,
        recordId: null,
        mapInstance: null,
        mapMarker: null,
        stationArray: [],
        analyticalMethodArray: [],
        dataSourceArray: [],
        metaDataArray: [],
        minorArray: [],

        init() {
            // Initialize Alpine component
            console.log('Empodat modal component initialized');
            
            // Clean up map on page unload
            window.addEventListener('beforeunload', () => {
                if (this.mapInstance) {
                    this.mapInstance.remove();
                    this.mapInstance = null;
                }
            });
        },

        async openModal(recordId) {
            try {
                console.log('Opening Empodat modal for record ID:', recordId);
                
                this.recordId = recordId;

                // Fetch record data
                const response = await fetch(
                    window.empodatRoutes.show.replace(':id', recordId)
                );

                if (!response.ok) {
                    console.error('Failed to fetch record data:', response.status, response.statusText);
                    throw new Error('Failed to fetch record data');
                }

                this.record = await response.json();
                console.log('Record data loaded:', this.record);

                // Build arrays for display
                this.buildStationArray();
                this.buildAnalyticalMethodArray();
                this.buildDataSourceArray();
                this.buildMetaDataArray();
                this.buildMinorArray();

                // Show the modal
                this.showModal = true;

                // Initialize map after modal is shown and DOM is updated
                // Use nextTick to ensure DOM is ready
                await this.$nextTick();
                
                // Additional delay to ensure modal transition is complete
                setTimeout(() => {
                    if (this.hasValidCoordinates()) {
                        this.initializeMap();
                    }
                }, 300);

            } catch (error) {
                console.error('Error opening Empodat modal:', error);
                alert('Failed to load record data. Please try again.');
            }
        },

        initializeMap() {
            console.log('Initializing map...');
            
            const mapContainer = document.getElementById('map');
            
            if (!mapContainer) {
                console.error('Map container not found');
                return;
            }

            // Clean up existing map instance if it exists
            if (this.mapInstance) {
                console.log('Removing existing map instance');
                this.mapInstance.remove();
                this.mapInstance = null;
                this.mapMarker = null;
            }

            try {
                const lat = parseFloat(this.record.station.latitude);
                const lng = parseFloat(this.record.station.longitude);
                
                console.log('Creating map with coordinates:', lat, lng);

                // Create new map instance
                this.mapInstance = L.map('map', {
                    center: [lat, lng],
                    zoom: 10,
                    scrollWheelZoom: false // Disable scroll zoom in modal
                });

                // Add tile layer
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(this.mapInstance);

                // Add marker
                this.mapMarker = L.marker([lat, lng])
                    .addTo(this.mapInstance)
                    .bindPopup(`
                        <div class="text-sm">
                            <strong>Station:</strong> ${this.record.station.name || 'Unknown'}<br>
                            <strong>Record ID:</strong> ${this.recordId}<br>
                            <strong>Coordinates:</strong> ${lat.toFixed(4)}, ${lng.toFixed(4)}
                        </div>
                    `);

                // Force map to recalculate its size
                setTimeout(() => {
                    this.mapInstance.invalidateSize();
                    
                    // Optional: Open popup automatically
                    this.mapMarker.openPopup();
                }, 100);

                console.log('Map initialized successfully');

            } catch (error) {
                console.error('Error creating map:', error);
            }
        },

        buildStationArray() {
            // `station_details` is built server-side: country resolved from
            // the relation, raw FKs and internal flags dropped, labels
            // applied. Falling back to the raw relation printed
            // "Country Relation: [object Object]" (issue #22).
            this.stationArray = this.buildLabelledArray(
                this.record?.station_details,
                this.record?.station,
            );
        },

        buildAnalyticalMethodArray() {
            // Server returns `analytical_method_details`: a flat
            // (label => value) map with codelist ids already resolved, the
            // "Other" free text collapsed into the same row and the DCT
            // labels applied (issue #22). Fall back to the raw relationship
            // if an older controller didn't populate it.
            this.analyticalMethodArray = this.buildLabelledArray(
                this.record?.analytical_method_details,
                this.record?.analytical_method,
            );
        },

        buildDataSourceArray() {
            this.dataSourceArray = this.buildLabelledArray(
                this.record?.data_source_details,
                this.record?.data_source,
            );
        },

        /**
         * Turn a server-built (label => value) map into the [label, value]
         * pairs the template renders. Keys of `details` are already
         * user-facing labels; keys of the raw `fallback` object are column
         * names and still need Title-Casing.
         */
        buildLabelledArray(details, fallback) {
            if (details && Object.keys(details).length > 0) {
                return Object.entries(details)
                    .filter(([, val]) => !this.isEmptyValue(val))
                    // Columns with no entry in config/empodat_field_labels.php
                    // arrive as raw column names ('doc_mg_cl', 'toc', 'cl');
                    // those still need Title-Casing. A real label always has
                    // a capital or a space, so anything without either is a
                    // raw column name.
                    .map(([key, val]) => [this.isRawColumnName(key) ? this.formatFieldName(key) : key, val]);
            }

            if (!fallback) {
                return [];
            }

            const excludedKeys = ['id', 'created_at', 'updated_at'];

            return Object.entries(fallback)
                .filter(([key, val]) => !excludedKeys.includes(key) && !this.isEmptyValue(val))
                .map(([key, val]) => [this.formatFieldName(key), val]);
        },

        isEmptyValue(val) {
            return val === null || val === undefined || val === '';
        },

        isRawColumnName(key) {
            return !/[A-Z ]/.test(key);
        },

        buildMetaDataArray() {
            // Already labelled server-side, same as the sections above.
            this.metaDataArray = this.buildLabelledArray(
                this.record?.matrix_data?.meta_data,
                null,
            );
        },

        buildMinorArray() {
            // Server returns `additional_details`: a flat (label => value)
            // map already resolved against PG list_* tables (so "Dpc Id: 2"
            // arrives here as "Precision of coordinates: Average (range
            // 10-100 m)"). Fall back to raw `minor` if the server didn't
            // populate the enriched payload yet (older controller / tests).
            const source = this.record?.additional_details ?? this.record?.minor;
            if (source) {
                const excludedKeys = ['id', 'created_at', 'updated_at', 'empodat_main_id'];
                // Legacy v1 stores '0000-00-00 00:00:00' / '00:00:00' / '0000' as
                // "no real value" sentinels. Some empodat_minor columns are also
                // cast to `datetime` server-side, which makes Carbon emit
                // '-000001-11-30T00:00:00.000000Z' for the zero-datetime — that
                // surfaces here as a junk date. Filter both forms.
                const zeroStringSentinels = new Set([
                    '0', '00', '000', '0000',
                    '0000-00-00', '0000-00-00 00:00:00',
                    '00:00:00', '00:00',
                ]);
                const isZeroValue = (val) => {
                    if (val === null || val === '' || val === 0) return true;
                    if (typeof val !== 'string') return false;
                    const trimmed = val.trim();
                    if (zeroStringSentinels.has(trimmed)) return true;
                    // Carbon-serialised zero-datetime: '-000001-11-30T...' or '-0001-11-30...'
                    return /^-0+1-11-30T/.test(trimmed);
                };

                this.minorArray = Object.entries(source)
                    .filter(([key, val]) => !excludedKeys.includes(key) && !isZeroValue(val))
                    .map(([key, val]) => [
                        this.formatFieldName(key),
                        val
                    ]);
            } else {
                this.minorArray = [];
            }
        },

        formatFieldName(fieldName) {
            // Convert snake_case to Title Case
            return fieldName
                .split('_')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        },

        hasValidCoordinates() {
            if (!this.record?.station) {
                return false;
            }
            
            const lat = parseFloat(this.record.station.latitude);
            const lng = parseFloat(this.record.station.longitude);
            
            // Check if coordinates exist, are valid numbers, and not both zero
            return !isNaN(lat) && !isNaN(lng) && 
                   lat >= -90 && lat <= 90 && 
                   lng >= -180 && lng <= 180 &&
                   (lat !== 0 || lng !== 0);
        },

        closeModal() {
            console.log('Closing modal');
            
            // Clean up map instance
            if (this.mapInstance) {
                console.log('Removing map instance');
                try {
                    this.mapInstance.remove();
                } catch (error) {
                    console.error('Error removing map:', error);
                }
                this.mapInstance = null;
                this.mapMarker = null;
            }

            // Reset all data
            this.showModal = false;
            this.record = null;
            this.recordId = null;
            this.stationArray = [];
            this.analyticalMethodArray = [];
            this.dataSourceArray = [];
            this.metaDataArray = [];
            this.minorArray = [];
        },

        // Helper method to format coordinates for display
        formatCoordinate(value, type) {
            const val = parseFloat(value);
            if (isNaN(val)) return 'N/A';
            
            const absVal = Math.abs(val);
            const degrees = Math.floor(absVal);
            const minutes = Math.floor((absVal - degrees) * 60);
            const seconds = ((absVal - degrees) * 60 - minutes) * 60;
            
            let direction = '';
            if (type === 'latitude') {
                direction = val >= 0 ? 'N' : 'S';
            } else {
                direction = val >= 0 ? 'E' : 'W';
            }
            
            return `${degrees}°${minutes}'${seconds.toFixed(2)}"${direction} (${val.toFixed(6)})`;
        },

        // Helper method to format record ID with spaces between triple digits
        formatRecordId(id) {
            if (!id) return 'N/A';
            
            // Convert to string and reverse it for easier processing
            const idStr = id.toString();
            const reversed = idStr.split('').reverse();
            
            // Add spaces every 3 digits
            const formatted = [];
            for (let i = 0; i < reversed.length; i++) {
                if (i > 0 && i % 3 === 0) {
                    formatted.push(' ');
                }
                formatted.push(reversed[i]);
            }
            
            // Reverse back and join
            return formatted.reverse().join('');
        }
    };
}

// Make it available globally for Alpine
window.empodatModal = empodatModal;