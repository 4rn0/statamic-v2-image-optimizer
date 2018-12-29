var getBytes = {

    methods: {

        getBytes: function(size) {

            var i = (size == 0) ? 0 : Math.floor( Math.log(size) / Math.log(1024) );
            return ( size / Math.pow(1024, i) ).toFixed(2) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i];

        }

    }

};

Vue.component('imageoptimizer-fieldtype', {

    mixins: [getBytes],

    template:   '<div v-if="isImage">' +
                    '<label class="block">ImageOptimizer</label>' +
                    '<div v-if="!loading">' +
                        '<small class="help-block" v-if="asset.imageoptimizer">{{ translate("addons.ImageOptimizer::settings.original") }} {{ getBytes(asset.imageoptimizer.original_size) }}<br>{{ translate("addons.ImageOptimizer::settings.reduced") }} {{ getBytes(filesize) }} ({{ percentage }}%)<br><a href="#" @click.prevent="doOptimize">{{ translate("addons.ImageOptimizer::settings.optimize-again") }}</a></small>' +
                        '<small class="help-block" v-else>{{ translate("addons.ImageOptimizer::settings.not-optimized") }}<br><a href="#" @click.prevent="doOptimize">{{ translate("addons.ImageOptimizer::settings.optimize-now") }}</a></small>' + 
                    '</div>' +
                    '<div v-else>' +
                        '<i class="icon icon-circular-graph animation-spin"></i>' +
                    '</div>' +
                '</div>',

    data: function() {
        
        return {

            assetEditor: false,
            loading: false

        };

    },

    computed: {

        asset: function() {

            return this.assetEditor ? this.assetEditor.asset : false;

        },

        isImage: function() {

            return this.asset && this.asset.is_image && this.asset.extension !== 'svg';

        },

        filesize: function() {

            return this.asset.imageoptimizer.original_size - this.asset.imageoptimizer.current_size;

        },

        percentage: function() {

            return ((this.filesize / this.asset.imageoptimizer.original_size) * 100).toFixed(2);

        }

    },

    methods: {
      
        doOptimize: function() {

            var url = '/cp/addons/image-optimizer/optimize';
            var params = { asset: this.asset.id };

            this.$http.post(url, params).then(function(response) {

                this.assetEditor.asset.imageoptimizer = response.data.asset.imageoptimizer;
                this.assetEditor.asset.last_modified_relative = response.data.asset.last_modified_relative;
                this.assetEditor.asset.size = response.data.asset.size;
                
                this.loading = false;

            })
            .catch(function(error) {

                this.loading = false;

            });

            this.loading = true;

        },

        getAssetEditor: function() {

            var parent = this.$parent;

            while (parent) {

                if (parent.asset) {
                    this.assetEditor = parent;
                }

                parent = parent.$parent ? parent.$parent : false;

            }

        },

        doCleanup: function() {

            var previous = this.$el.previousElementSibling;

            while (previous) {

                previous.parentNode.removeChild(previous);
                previous = previous.previousElementSibling;

            }

        }

    },

    ready: function() {

        this.getAssetEditor();
        this.doCleanup();        

    }

});

Vue.component('imageoptimizer-statistics-fieldtype', {

    mixins: [Fieldtype, getBytes],

    template:   '<div class="section-fieldtype publish-fields">' +
                    '<div class="stats form-group w-1/2">' +
                        '<span class="icon icon-images"></span>' +
                        '<span v-if="stats.images.length">' +
                            '<div>{{ translate("addons.ImageOptimizer::settings.optimized") }} {{ stats.optimized.length }} of {{ stats.images.length }} {{ translate("addons.ImageOptimizer::settings.assets") }}</div>' +
                            '<div v-if="filesize">{{ translate("addons.ImageOptimizer::settings.reduced") }} {{ getBytes(filesize) }} ({{ percentage }}%)</div>' +
                        '</span>' +
                        '<span v-else>' +
                            '<div>{{ translate("addons.ImageOptimizer::settings.empty") }}</div>' +
                        '</span>' +
                    '</div>' +
                    '<div class="form-group w-1/2">' +
                        '<div v-if="optimizing">' +
                            '<div class="progress"><div class="progress-bar" :style="{ width: progress }"></div></div>' +
                            '<small>{{ translate("addons.ImageOptimizer::settings.optimizing") }} {{ index + 1 }} of {{ stats.images.length }} ({{ current }})</small>' +
                        '</div>' +
                        '<div class="lg:float-right" v-if="stats.images.length && !optimizing">' +
                            '<button class="btn btn-primary" @click="doOptimizeNew" v-if="stats.images.length > stats.optimized.length">{{ translate("addons.ImageOptimizer::settings.optimize-new") }}</button>' +
                            '<button class="btn" @click="doOptimizeAll">{{ translate("addons.ImageOptimizer::settings.optimize-again") }}</button>' +
                        '</div>' +
                    '</div>' +
                '</div>',

    data: function() {
        
        return {

            autoBindChangeWatcher: false,
            optimizing: false,
            store: false,
            list: [],
            index: 0,

        };

    },

    computed: {

        stats: function() {

            return this.store ? this.store : this.data;
            
        },

        filesize: function() {

            return this.stats.original_size - this.stats.current_size;

        },

        percentage: function() {

            return ((this.filesize / this.stats.original_size) * 100).toFixed(2);

        },

        progress: function() {

            return ((this.index / (this.list.length - 1)) * 100) + '%';

        },

        current: function() {

            return this.list[this.index];

        }

    },

    methods: {
        
        doOptimizeNew: function() {

            var optimized = this.data.optimized;

            this.list = this.data.images.filter(function(item) {

                return optimized.indexOf(item) < 0;

            });

            this.doOptimize();

        },

        doOptimizeAll: function() {

            this.list = this.data.images;
            this.doOptimize();

        },

        doOptimize: function() {

            var url = '/cp/addons/image-optimizer/optimize';
            var params = { asset: this.list[this.index] };

            this.$http.post(url, params).then(function(response) {

                this.store = response.data.statistics;

                if (this.index < this.list.length - 1) {

                    this.$nextTick(this.doOptimize);
                    this.index++;

                }

                else {

                    this.optimizing = false;
                    this.index = 0;

                }

            })
            .catch(function(error) {

                this.optimizing = false;
                this.index = 0;

            });

            this.optimizing = true;

        }

    }

});
