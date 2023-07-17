<script>
import ApexCharts from 'apexcharts';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import axios from "axios";
import {defineComponent} from "vue";
import VueApexCharts from 'vue3-apexcharts'
import moment from "moment";
import { useToast } from "vue-toastification";

export default defineComponent({
    components: {
        AuthenticatedLayout,
        apexchart: VueApexCharts
    },
    props: ['ticker', 'wallet'],
    data() {
        return {
            active: this.ticker,
            user: this.$page.props.auth.user,
            transactions: this.$page.props.userTransactions,
            portfolio: this.$page.props.userPortfolio,
            watchlist: this.$page.props.userWatchlist,
            searchQuery: '',
            toast: useToast(),
            company: null,
            details: null,
            historical: null,
            chart: null,
            options: null,
            dropdownVisible: false,
            buy_modal: false,
            sell_modal: false,
            wallet: this.wallet,
            stock_id: '',
            quantity: '',
            price: '',
            error: '',
            message: '',
            start_date: null,
            end_date: null,
            date_range: 'Last 30 Days',
            series: [{
                name: 'Historical',
                data: []  // This will be populated with your historical data
            }],
            chartOptions: {
                // Various options for the chart
                chart: {
                    id: 'vuechart-example'
                },
                xaxis: {
                    categories: []  // This will be populated with the dates from your historical data
                }
            }
        }
    },
    methods: {
        getMainChartOptions() {
            let mainChartColors = {
                borderColor: '#F3F4F6',
                labelColor: '#6B7280',
                opacityFrom: 0.45,
                opacityTo: 0,
            }

            // Your chart options here...
            return {
                chart: {
                    height: 420,
                    type: 'area',
                    fontFamily: 'Inter, sans-serif',
                    foreColor: mainChartColors.labelColor,
                    toolbar: {
                        show: false
                    }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        enabled: true,
                        opacityFrom: mainChartColors.opacityFrom,
                        opacityTo: mainChartColors.opacityTo
                    }
                },
                dataLabels: {
                    enabled: false
                },
                tooltip: {
                    style: {
                        fontSize: '14px',
                        fontFamily: 'Inter, sans-serif',
                    },
                },
                grid: {
                    show: true,
                    borderColor: mainChartColors.borderColor,
                    strokeDashArray: 1,
                    padding: {
                        left: 35,
                        bottom: 15
                    }
                },
                markers: {
                    size: 5,
                    strokeColors: '#ffffff',
                    hover: {
                        size: undefined,
                        sizeOffset: 3
                    }
                },
                series: [
                    {
                        name: 'Revenue',
                        data: [6356, 6218, 6156, 6526, 6356, 6256, 6056],
                        color: '#1A56DB'
                    },
                    // {
                    //     name: 'Revenue (previous period)',
                    //     data: [6556, 6725, 6424, 6356, 6586, 6756, 6616],
                    //     color: '#FDBA8C'
                    // }
                ],
                xaxis: {
                    categories: ['01 Feb', '02 Feb', '03 Feb', '04 Feb', '05 Feb', '06 Feb', '07 Feb'],
                    labels: {
                        style: {
                            colors: [mainChartColors.labelColor],
                            fontSize: '14px',
                            fontWeight: 500,
                        },
                    },
                    axisBorder: {
                        color: mainChartColors.borderColor,
                    },
                    axisTicks: {
                        color: mainChartColors.borderColor,
                    },
                    crosshairs: {
                        show: true,
                        position: 'back',
                        stroke: {
                            color: mainChartColors.borderColor,
                            width: 1,
                            dashArray: 10,
                        },
                    },
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: [mainChartColors.labelColor],
                            fontSize: '14px',
                            fontWeight: 500,
                        },
                        formatter: function (value) {
                            return '$' + value;
                        }
                    },
                },
                legend: {
                    fontSize: '14px',
                    fontWeight: 500,
                    fontFamily: 'Inter, sans-serif',
                    labels: {
                        colors: [mainChartColors.labelColor]
                    },
                    itemMargin: {
                        horizontal: 10
                    }
                },
                responsive: [
                    {
                        breakpoint: 1024,
                        options: {
                            xaxis: {
                                labels: {
                                    show: false
                                }
                            }
                        }
                    }
                ]
            }
        },
        async getSnapshot() {
            try {
                axios.defaults.headers.common = {};
                const response = await axios.get(`/api/snapshot/${this.active.ticker}`);

                if (response.status === 200) {
                    this.company = response.data.ticker;
                    this.price = this.company.prevDay.c;
                    await this.getDetails(this.company.ticker);
                } else {
                    this.company = null;
                }
            } catch (error) {
                console.error('Error fetching snapshot:', error);
            }
        },
        async getDetails(ticker) {
            console.log(ticker);
            try {
                const response = await axios.get(`/api/details/${ticker}`);

                if (response.data) {
                    this.details = response.data.results;
                    console.log("Details: " + this.details);
                } else {
                    this.details = null;
                }
            } catch (error) {
                console.error('Error fetching details:', error);
            }
        },
        async getHistorical() {
            try {
                let response = await axios.get(`/api/historical/${this.active.ticker}/${this.start_date}/${this.end_date}`);
                this.historical = response.data.results;
                console.log("Historical: " + this.historical);

                // Populate the series data and xaxis categories
                // this.series[0].data = this.historicalData.map(result => result.vw);  // Use whichever data property you want for the series data
                // this.chartOptions.xaxis.categories = this.historicalData.map(result => new Date(result.t).toLocaleDateString());  // Convert the timestamps to dates
            } catch (error) {
                console.error('Error fetching details:', error);
            }
        },
        closeCompany() {
            this.$emit('close');
        },
        reloadSnapshots() {
            this.$emit('reload-snapshots');
        },
        calculateDateRange(range) {
            this.end_date = moment().format('YYYY-MM-DD'); // end date is always today

            switch(range) {
                case 'Last 7 Days':
                    this.start_date = moment().subtract(7, 'days').format('YYYY-MM-DD');
                    break;
                case 'Last 30 Days':
                    this.start_date = moment().subtract(30, 'days').format('YYYY-MM-DD');
                    break;
                case 'Last 90 Days':
                    this.start_date = moment().subtract(90, 'days').format('YYYY-MM-DD');
                    break;
            }
        },
        async addToWatchlist() {
            try {
                console.log('Adding to watchlist');
                const response = await axios.post(`/api/watchlist/add`, {
                    stock_id: this.active.ticker,
                    user_id: this.user.id
                });

                if (response.data.success) {
                    this.toast.success('Successfully added to watchlist!');
                    console.log('Stock added to watchlist');
                    this.reloadSnapshots();
                } else {
                    this.toast.error('Item already on watchlist.')
                    console.log('Failed to add to watchlist');
                }
            } catch (error) {
                console.error('Error adding to watchlist:', error);
            }
        },
        async removeFromWatchlist() {
            try {
                console.log('Removing from watchlist');
                const response = await axios.post(`/api/watchlist/remove`, {
                    stock_id: this.active.ticker,
                    user_id: this.user.id
                });

                if (response.data.success) {
                    this.toast.success('Successfully removed from watchlist!');
                    console.log('Stock removed from watchlist');
                    this.removeItemFromWatchlistArray(this.active.ticker);
                    this.reloadSnapshots();
                } else {
                    this.toast.error('Item not on watchlist.')
                    console.log('Failed to add to watchlist');
                }
            } catch (error) {
                console.error('Error removing from watchlist:', error);
            }
        },
        removeItemFromWatchlistArray(stockIdToRemove) {
            this.watchlist = this.watchlist.filter(stock => stock.stock_id !== stockIdToRemove);
        },
        addItemToWatchlistArray(stockIdToAdd) {
            this.watchlist.push(stockIdToAdd);
        },
        async buyStock() {
            try {
                const response = await axios.post('/api/stocks/buy', {
                    stock_id: this.active.ticker,
                    quantity: this.quantity,
                    user_id: this.user.id,
                    price: this.price,
                });

                if (response.data.success) {
                    this.toast.success('Successfully bought!');
                    console.log('Stock bought');
                    this.buy_modal = false;
                    this.reloadSnapshots();
                    // this.$inertia.get('/portfolio');
                } else {
                    this.toast.error('Something went wrong buying!')
                    console.log('Failed to buy stock');
                }
            } catch (error) {
                console.error('Error buying stock:', error);
            }
        },
        async sellStock() {
            try {
                const response = await axios.post('/api/stocks/sell', {
                    stock_id: this.active.ticker,
                    quantity: this.quantity,
                    user_id: this.user.id,
                    price: this.price,
                })
                if (response.data.success) {
                    this.toast.success('Successfully sold!');
                    console.log('Stock sold');
                    this.sell_modal = false;
                    this.reloadSnapshots();
                    // this.$inertia.get('/portfolio');
                } else {
                    this.toast.error('Something went wrong selling!')
                    console.log('Failed to sell stock');
                }
            } catch (error) {
                console.error('Error selling stock:', error);
            }
        },
        consolidateWatchlistStockIds() {
            // Concatenate the portfolio and watchlist arrays
            let allStocks = this.watchlist;

            // Create a new array containing only unique stock_ids
            let uniqueStockIds = [...new Set(allStocks.map(item => item.stock_id))];

            return uniqueStockIds;
        }
    },
    watch: {
        date_range: function(newVal, oldVal) {
                this.getHistorical();
                this.calculateDateRange(newVal);
        }
    },
    computed: {
        total_price() {
            return (this.price * this.quantity).toFixed(2);
        },
        remaining_balance() {
            return parseFloat((this.wallet.balance - this.total_price).toFixed(2));
        },
        new_balance() {
            const balance = parseFloat(this.wallet.balance);
            const totalPrice = parseFloat(this.total_price);
            return parseFloat((balance + totalPrice).toFixed(2));
        },
        portfolio_watchlist_stocks() {
            return this.consolidateWatchlistStockIds();
        },
        onWatchlist() {
            return !!this.portfolio_watchlist_stocks.includes(this.active.ticker);
        }
    },
    mounted() {
        this.calculateDateRange(this.date_range);
        this.getSnapshot();
        this.options = this.getMainChartOptions();
        const chart = new ApexCharts(document.getElementById('main-chart'), this.options);
        chart.render();
    }
})

</script>
<template>
    <div class="p-8 mx-auto max-w-screen-xl items-center flex flex-wrap" >

        <!-- region Buttons -->
        <div class="p-4 mb-4 relative flex flex-col w-full bg-white rounded-lg shadow sm:p-4 xl:p-4">
            <div class="flex justify-between items-center">
                <h2 class="font-semibold px-4 text-xl text-gray-800 leading-tight">{{ this.active.name ?? '' }}</h2>
                <div class="flex space-x-4">
                    <button v-if="!onWatchlist" @click="addToWatchlist" class="inline-block px-6 py-3 font-bold text-center text-white uppercase align-middle transition-all rounded-lg cursor-pointer bg-gradient-to-tl from-blue-500 to-violet-500 leading-normal text-xs ease-in tracking-tight-rem shadow-xs bg-150 bg-x-25 hover:-translate-y-px active:opacity-85 hover:shadow-md">
                        Add Watchlist
                    </button>
                    <button v-if="onWatchlist" @click="removeFromWatchlist" class="inline-block px-6 py-3 font-bold text-center text-white uppercase align-middle transition-all rounded-lg cursor-pointer bg-gradient-to-tl from-blue-500 to-violet-500 leading-normal text-xs ease-in tracking-tight-rem shadow-xs bg-150 bg-x-25 hover:-translate-y-px active:opacity-85 hover:shadow-md">
                        Remove Watchlist
                    </button>
                    <button @click="buy_modal = true" class="inline-block px-6 py-3 font-bold text-center text-white uppercase align-middle transition-all rounded-lg cursor-pointer bg-gradient-to-tl from-blue-500 to-violet-500 leading-normal text-xs ease-in tracking-tight-rem shadow-xs bg-150 bg-x-25 hover:-translate-y-px active:opacity-85 hover:shadow-md">
                        Buy
                    </button>
                    <button v-show="quantity > 0" @click="sell_modal = true" class="inline-block px-6 py-3 font-bold text-center text-white uppercase align-middle transition-all rounded-lg cursor-pointer bg-gradient-to-tl from-blue-500 to-violet-500 leading-normal text-xs ease-in tracking-tight-rem shadow-xs bg-150 bg-x-25 hover:-translate-y-px active:opacity-85 hover:shadow-md">
                        Sell
                    </button>
                    <button @click="closeCompany" class="inline-block px-6 py-3 font-bold text-center text-white uppercase align-middle transition-all rounded-lg cursor-pointer bg-gradient-to-tl from-blue-500 to-violet-500 leading-normal text-xs ease-in tracking-tight-rem shadow-xs bg-150 bg-x-25 hover:-translate-y-px active:opacity-85 hover:shadow-md">
                        Close
                    </button>
                </div>
            </div>
        </div>
        <!-- endregion -->

        <!-- region Graph 1 -->
        <div class="p-4 relative flex flex-col w-full bg-white rounded-lg shadow sm:p-6 xl:p-8">
            <div class="flex justify-between items-center mb-4">
                <div class="flex-shrink-0">
                                <span
                                    class="text-2xl font-bold leading-none text-gray-900 sm:text-3xl ">$45,385</span>
                    <h3 class="text-base font-normal text-gray-500 ">Sales this week</h3>
                </div>
                <div
                    class="flex flex-1 justify-end items-center text-base font-bold text-green-500">
                    12.5%
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                         xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd"
                              d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z"
                              clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
            <div id="main-chart"></div>
<!--            <apexchart type="line" :options="chartOptions" :series="series"></apexchart>-->
            <div class="flex justify-between items-center pt-3 mt-5 border-t border-gray-200 sm:pt-6">
                <div>
                    <button
                        class="inline-flex items-center p-2 text-sm font-medium text-center text-gray-500 rounded-lg hover:text-gray-900  "
                        type="button" @click="dropdownVisible = !dropdownVisible" data-dropdown-toggle="weekly-sales-dropdown">{{ this.date_range }}<svg
                        class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 9l-7 7-7-7"></path>
                    </svg></button>
                    <!-- Dropdown menu -->
                    <div class="z-50 my-4 text-base list-none bg-white rounded divide-y divide-gray-100 shadow"
                         id="weekly-sales-dropdown"
                         v-show="dropdownVisible">
                        <ul class="py-1" role="none">
                            <li @click="date_range = 'Last 7 Days'; dropdownVisible = false;">
                                <p class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Last 7 days</p>
                            </li>
                            <li @click="date_range = 'Last 30 Days'; dropdownVisible = false;">
                                <p class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Last 30 days</p>
                            </li>
                            <li @click="date_range = 'Last 90 Days'; dropdownVisible = false;">
                                <p class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Last 90 days</p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- endregion -->

        <!-- region Graph 2 -->
        <div class="grid grid-cols-1 gap-4 mt-4 w-full md:grid-cols-2 xl:grid-cols-3">
            <div class="p-4 bg-white rounded-lg shadow sm:p-6 xl:p-8">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                                        <span
                                            class="text-2xl font-bold leading-none text-gray-900 sm:text-3xl ">2,340</span>
                        <h3 class="text-base font-normal text-gray-500 ">New products this
                            week</h3>
                    </div>
                    <div
                        class="flex flex-1 justify-end items-center ml-5 w-0 text-base font-bold text-green-500">
                        14.6%
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                             xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                  d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z"
                                  clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <div id="new-products-chart"></div>
                <!-- Card Footer -->
                <div
                    class="flex justify-between items-center pt-3 border-t border-gray-200 sm:pt-6">
                    <div>
                        <button
                            class="inline-flex items-center p-2 text-sm font-medium text-center text-gray-500 rounded-lg hover:text-gray-900  "
                            type="button" data-dropdown-toggle="new-products-dropdown">Last 7 days <svg
                            class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 9l-7 7-7-7"></path>
                        </svg></button>
                        <!-- Dropdown menu -->
                        <div class="hidden z-50 my-4 text-base list-none bg-white rounded divide-y divide-gray-100 shadow"
                             id="new-products-dropdown">
                            <div class="py-3 px-4" role="none">
                                <p class="text-sm font-medium text-gray-900 truncate "
                                   role="none">
                                    Sep 16, 2021 - Sep 22, 2021
                                </p>
                            </div>
                            <ul class="py-1" role="none">
                                <li>
                                    <a href="#"
                                       class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100   "
                                       role="menuitem">Yesterday</a>
                                </li>
                                <li>
                                    <a href="#"
                                       class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100   "
                                       role="menuitem">Today</a>
                                </li>
                                <li>
                                    <a href="#"
                                       class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100   "
                                       role="menuitem">Last 7 days</a>
                                </li>
                                <li>
                                    <a href="#"
                                       class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100   "
                                       role="menuitem">Last 30 days</a>
                                </li>
                                <li>
                                    <a href="#"
                                       class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100   "
                                       role="menuitem">Last 90 days</a>
                                </li>
                            </ul>
                            <div class="py-1" role="none">
                                <a href="#"
                                   class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100   "
                                   role="menuitem">Custom...</a>
                            </div>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <a href="#"
                           class="inline-flex items-center p-2 text-xs font-medium uppercase rounded-lg text-primary-700 sm:text-sm hover:bg-gray-100">
                            Products Report
                            <svg class="ml-1 w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-white rounded-lg shadow sm:p-6 xl:p-8">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                                        <span
                                            class="text-2xl font-bold leading-none text-gray-900 sm:text-3xl ">5,355</span>
                        <h3 class="text-base font-normal text-gray-500 ">Visitors this
                            week</h3>
                    </div>
                    <div
                        class="flex flex-1 justify-end items-center ml-5 w-0 text-base font-bold text-green-500">
                        32.9%
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                             xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                  d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z"
                                  clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <div id="visitors-chart"></div>
                <!-- Card Footer -->
                <div
                    class="flex items-center justify-between border-t border-gray-200 pt-3 sm:pt-6 mt-3.5">
                    <div>
                        <button
                            class="inline-flex items-center p-2 text-sm font-medium text-center text-gray-500 rounded-lg hover:text-gray-900  "
                            type="button" data-dropdown-toggle="visitors-dropdown">Last 7 days <svg
                            class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 9l-7 7-7-7"></path>
                        </svg></button>
                        <!-- Dropdown menu -->
                        <div class="hidden z-50 my-4 text-base list-none bg-white rounded divide-y divide-gray-100 shadow"
                             id="visitors-dropdown">
                            <div class="py-3 px-4" role="none">
                                <p class="text-sm font-medium text-gray-900 truncate "
                                   role="none">
                                    Sep 16, 2021 - Sep 22, 2021
                                </p>
                            </div>
                            <ul class="py-1" role="none">
                                <li>
                                    <a href="#"
                                       class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100   "
                                       role="menuitem">Yesterday</a>
                                </li>
                                <li>
                                    <a href="#"
                                       class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100   "
                                       role="menuitem">Today</a>
                                </li>
                                <li>
                                    <a href="#"
                                       class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100   "
                                       role="menuitem">Last 7 days</a>
                                </li>
                                <li>
                                    <a href="#"
                                       class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100   "
                                       role="menuitem">Last 30 days</a>
                                </li>
                                <li>
                                    <a href="#"
                                       class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100   "
                                       role="menuitem">Last 90 days</a>
                                </li>
                            </ul>
                            <div class="py-1" role="none">
                                <a href="#"
                                   class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100   "
                                   role="menuitem">Custom...</a>
                            </div>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <a href="#"
                           class="inline-flex items-center p-2 text-xs font-medium uppercase rounded-lg text-primary-700 sm:text-sm hover:bg-gray-100">
                            Visits Report
                            <svg class="ml-1 w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-white rounded-lg shadow sm:p-6 xl:p-8">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                                        <span
                                            class="text-2xl font-bold leading-none text-gray-900 sm:text-3xl ">385</span>
                        <h3 class="text-base font-normal text-gray-500 ">User signups this
                            week</h3>
                    </div>
                    <div
                        class="flex flex-1 justify-end items-center ml-5 w-0 text-base font-bold text-red-500">
                        -2.7%
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                             xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                  d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l2.293-2.293a1 1 0 011.414 0z"
                                  clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <div id="week-signups-chart"></div>
                <!-- Card Footer -->
                <div
                    class="flex justify-between items-center pt-3 border-t border-gray-200 sm:pt-6">
                    <div>
                        <button
                            class="inline-flex items-center p-2 text-sm font-medium text-center text-gray-500 rounded-lg hover:text-gray-900  "
                            type="button" data-dropdown-toggle="week-signups-dropdown">Last 7 days <svg
                            class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 9l-7 7-7-7"></path>
                        </svg></button>
                        <!-- Dropdown menu -->
                        <div class="hidden z-50 my-4 text-base list-none bg-white rounded divide-y divide-gray-100 shadow"
                             id="week-signups-dropdown">
                            <div class="py-3 px-4" role="none">
                                <p class="text-sm font-medium text-gray-900 truncate "
                                   role="none">
                                    Sep 16, 2021 - Sep 22, 2021
                                </p>
                            </div>
                            <ul class="py-1" role="none">
                                <li>
                                    <a href="#"
                                       class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100   "
                                       role="menuitem">Yesterday</a>
                                </li>
                                <li>
                                    <a href="#"
                                       class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100   "
                                       role="menuitem">Today</a>
                                </li>
                                <li>
                                    <a href="#"
                                       class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100   "
                                       role="menuitem">Last 7 days</a>
                                </li>
                                <li>
                                    <a href="#"
                                       class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100   "
                                       role="menuitem">Last 30 days</a>
                                </li>
                                <li>
                                    <a href="#"
                                       class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100   "
                                       role="menuitem">Last 90 days</a>
                                </li>
                            </ul>
                            <div class="py-1" role="none">
                                <a href="#"
                                   class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100   "
                                   role="menuitem">Custom...</a>
                            </div>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <a href="#"
                           class="inline-flex items-center p-2 text-xs font-medium uppercase rounded-lg text-primary-700 sm:text-sm hover:bg-gray-100">
                            Users Report
                            <svg class="ml-1 w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- endregion -->

        <!-- region Graph 3 -->
        <div class="p-4 mt-4 flex flex-col w-full bg-white rounded-lg shadow sm:p-6 xl:p-8">
            <h3 class="mb-4 text-xl font-bold">General information</h3>
            <dl v-if="this.details" class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</dt>
                    <dd class="text-sm font-semibold text-gray-900">{{ this.details.name ?? '' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Primary Exchange</dt>
                    <dd class="text-sm font-semibold text-gray-900">{{ this.details.primary_exchange ?? '' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ticker</dt>
                    <dd class="text-sm font-semibold text-gray-900">{{ this.details.ticker ?? '' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Market Cap</dt>
                    <dd class="text-sm font-semibold text-gray-900">{{ this.details.market_cap ?? '' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Website</dt>
                    <dd class="text-sm font-semibold text-gray-900"><a :href="this.details.homepage_url">{{ this.details.homepage_url ?? '' }}</a></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Department</dt>
                    <dd class="text-sm font-semibold text-gray-900">{{ this.details.name ?? '' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone Number</dt>
                    <dd class="text-sm font-semibold text-gray-900">{{ this.details.phone_number ?? '' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                    <dd class="text-sm font-semibold text-gray-900">{{ this.details.description ?? '' }}</dd>
                </div>
            </dl>
        </div>
        <!-- endregion -->

        <!-- region Buy Stock Modal -->
        <div v-show="buy_modal" class="overflow-y-auto overflow-x-hidden fixed inset-0 z-50 flex items-center justify-center h-modal sm:h-full" id="product-modal">
            <div class="relative px-4 w-full max-w-2xl h-full md:h-auto">
                <!-- Modal content -->
                <div class="relative bg-white rounded-lg shadow dark:bg-gray-800">
                    <!-- Modal header -->
                    <div class="flex justify-between items-start p-5 rounded-t border-b dark:border-gray-700">
                        <h3 class="text-xl font-semibold dark:text-white">
                            Buy Stock
                        </h3>
                        <button @click="buy_modal = false; quantity=0" type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700" data-modal-toggle="product-modal">
                            Close
                        </button>
                    </div>
                    <!-- Modal body -->
                    <div class="p-6 space-y-6">
                        <form @submit.prevent="buyStock" action="#">
                            <div class="grid grid-cols-6 gap-6">
                                <div class="col-span-6 sm:col-span-3">
                                    <label for="stock-symbol" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Stock Symbol</label>
                                    <input type="text" name="stock-symbol" id="stock-symbol" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="AAPL" v-model="active.ticker" readonly>
                                </div>
                                <div class="col-span-6 sm:col-span-3">
                                    <label for="stock-name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Stock Name</label>
                                    <input type="text" name="stock-name" id="stock-name" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Apple Inc." v-model="active.name" readonly>
                                </div>
                                <div class="col-span-6 sm:col-span-3">
                                    <label for="stock-price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Last Closing Price</label>
                                    <input type="text" name="stock-price" id="stock-price" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" v-model="price" placeholder="$150.00" readonly>
                                </div>
                                <div class="col-span-6 sm:col-span-3">
                                    <label for="quantity" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Quantity</label>
                                    <input type="number" name="quantity" id="quantity" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" v-model="quantity" min="1">
                                </div>
                                <div class="col-span-6 sm:col-span-3">
                                    <label for="total-price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Total Price</label>
                                    <input type="text" name="total-price" id="total-price" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" v-model="total_price" readonly>
                                </div>
                                <div class="col-span-6 sm:col-span-3">
                                    <label for="total-price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Wallet Balance</label>
                                    <input type="text" name="total-price" id="remaining-balance" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" v-model="remaining_balance" readonly>
                                </div>
                            </div>
                            <div class="flex justify-end pt-5">
                                <button type="submit" class="bg-green-500 text-white rounded-lg px-6 py-2 hover:bg-green-600">Buy</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- endregion End of Modal -->

        <!-- region Sell Modal -->
        <div v-show="sell_modal" class="overflow-y-auto overflow-x-hidden fixed inset-0 z-50 flex items-center justify-center h-modal sm:h-full" id="product-modal">
            <div class="relative px-4 w-full max-w-2xl h-full md:h-auto">
                <!-- Modal content -->
                <div class="relative bg-white rounded-lg shadow dark:bg-gray-800">
                    <!-- Modal header -->
                    <div class="flex justify-between items-start p-5 rounded-t border-b dark:border-gray-700">
                        <h3 class="text-xl font-semibold dark:text-white">
                            Buy Stock
                        </h3>
                        <button @click="sell_modal = false; quantity=0" type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700" data-modal-toggle="product-modal">
                            Close
                        </button>
                    </div>
                    <!-- Modal body -->
                    <div class="p-6 space-y-6">
                        <form @submit.prevent="sellStock">
                            <div class="grid grid-cols-6 gap-6">
                                <div class="col-span-6 sm:col-span-3">
                                    <label for="stock-symbol" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Stock Symbol</label>
                                    <input type="text" name="stock-symbol" id="stock-symbol" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="AAPL" v-model="active.ticker" readonly>
                                </div>
                                <div class="col-span-6 sm:col-span-3">
                                    <label for="stock-name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Stock Name</label>
                                    <input type="text" name="stock-name" id="stock-name" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Apple Inc." v-model="active.name" readonly>
                                </div>
                                <div class="col-span-6 sm:col-span-3">
                                    <label for="stock-price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Last Closing Price</label>
                                    <input type="text" name="stock-price" id="stock-price" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" v-model="price" placeholder="$150.00" readonly>
                                </div>
                                <div class="col-span-6 sm:col-span-3">
                                    <label for="quantity" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Quantity</label>
                                    <input type="number" name="quantity" id="quantity" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" v-model="quantity" min="1">
                                </div>
                                <div class="col-span-6 sm:col-span-3">
                                    <label for="total-price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Total Price</label>
                                    <input type="text" name="total-price" id="total-price" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" v-model="total_price" readonly>
                                </div>
                                <div class="col-span-6 sm:col-span-3">
                                    <label for="total-price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Wallet Balance</label>
                                    <input type="text" name="total-price" id="remaining-balance" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" v-model="new_balance" readonly>
                                </div>
                            </div>
                            <div class="flex justify-end pt-5">
                                <button type="submit" class="bg-green-500 text-white rounded-lg px-6 py-2 hover:bg-green-600">Sell</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- endregion End of Modal -->

    </div>
</template>

<style scoped>

</style>
