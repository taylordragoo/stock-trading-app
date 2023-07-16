<script>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import axios from 'axios';
import {defineComponent} from "vue";
import Company from "@/Pages/Company.vue";
import {Head} from "@inertiajs/vue3";

export default defineComponent({
    components: {
        Head,
        Company,
        AuthenticatedLayout,
    },
    data() {
        return {
            searchQuery: '',
            transactions: this.$page.props.userTransactions,
            portfolio: this.$page.props.userPortfolio,
            watchlist: this.$page.props.userWatchlist,
            wallet: this.$page.props.userWallet,
            searchResults: [],
            active: null,
        }
    },
    methods: {
        async searchStocks() {
            if (this.searchQuery && this.searchQuery.length > 0) {
                try {
                    axios.defaults.headers.common = {};
                    const response = await axios.get(`/api/search/${this.searchQuery}`)
                        .then(response => {
                            this.searchResults = response.data;
                            console.log("Search: " + this.searchResults);
                        })
                        .catch(error => {
                            console.error('Error searching stocks:', error);
                        });

                } catch (error) {
                    console.error('Error searching stocks:', error);
                }
            } else {
                this.searchResults = [];
            }
        },
        async activate(ticker) {
            this.searchQuery = ticker;
            await this.searchStocks();
            const selectedStock = this.searchResults.find(stock => stock.ticker === ticker);
            this.active = selectedStock;
        },
        formattedDate(newDate) {
            let date = new Date(newDate);
            let day = String(date.getDate()).padStart(2, '0');
            let month = String(date.getMonth() + 1).padStart(2, '0'); // January is 0!
            let year = date.getFullYear();

            return month + '/' + day + '/' + year.toString().substr(-2);
        }
    }
});

</script>

<template>
    <Head title="Orders" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Orders</h2>
        </template>

        <div v-if="active === null" class="p-8 mx-auto max-w-screen-xl items-center flex">
            <div class="relative flex flex-col w-full min-w-0 mb-0 break-words bg-white border-0 border-transparent border-solid shadow-md rounded-lg bg-clip-border">
                <div class="p-6 pb-0 mb-0 bg-white rounded-t-2xl">
                    <h6>&nbsp;</h6>
                </div>
                <div class="flex-auto px- pt-0 pb-2">
                    <div class="p-0 overflow-x-auto">
                        <table class="items-center w-full mb-0 align-top border-gray-200 text-slate-500">
                            <thead class="align-bottom">
                            <tr>
                                <th class="px-6 py-3 font-bold uppercase align-middle bg-transparent border-b border-gray-200 shadow-none text-xxs border-b-solid tracking-none whitespace-nowrap">Ticker</th>
                                <th class="px-6 py-3 font-bold uppercase align-middle bg-transparent border-b border-gray-200 shadow-none text-xxs border-b-solid tracking-none whitespace-nowrap">Quantity</th>
                                <th class="px-6 py-3 font-bold uppercase align-middle bg-transparent border-b border-gray-200 shadow-none text-xxs border-b-solid tracking-none whitespace-nowrap">Type</th>
                                <th class="px-6 py-3 font-bold uppercase align-middle bg-transparent border-b border-gray-200 shadow-none text-xxs border-b-solid tracking-none whitespace-nowrap">Buy Price (Avg)</th>
                                <th class="px-6 py-3 font-bold uppercase align-middle bg-transparent border-b border-gray-200 shadow-none text-xxs border-b-solid tracking-none whitespace-nowrap">Total Value</th>
                                <th class="px-6 py-3 font-bold uppercase align-middle bg-transparent border-b border-gray-200 shadow-none text-xxs border-b-solid tracking-none whitespace-nowrap">Date</th>
                                <th class="px-6 py-3 font-semibold capitalize align-middle bg-transparent border-b border-gray-200 border-solid shadow-none tracking-none whitespace-nowrap"></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="t in transactions">
                                <td class="p-2 leading-normal text-center align-middle bg-transparent border-b text-sm whitespace-nowrap shadow-transparent">
                                    <div class="px-2 py-1">
                                        <div class="text-center">
                                            <h6 class="mb-0 leading-normal text-gray-800 text-sm">{{ t.stock_id }}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-2 leading-normal text-center align-middle bg-transparent border-b text-sm whitespace-nowrap shadow-transparent">
                                    <div class="px-2 py-1">
                                        <div class="text-center">
                                            <h6 class="mb-0 leading-normal text-gray-800 text-sm">{{ t.quantity }}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-2 leading-normal text-center align-middle bg-transparent border-b text-sm whitespace-nowrap shadow-transparent">
                                    <p class="mb-0 font-semibold leading-tight text-xs text-gray-800">{{ t.type }}</p>
                                </td>
                                <td class="p-2 text-center align-middle bg-transparent border-b whitespace-nowrap shadow-transparent">
                                    <span class="font-semibold leading-tight text-xs text-gray-800">{{ t.price_per_stock }}</span>
                                </td>
                                <td class="p-2 text-center align-middle bg-transparent border-b whitespace-nowrap shadow-transparent">
                                    <span class="font-semibold leading-tight text-xs text-gray-800">{{ t.total_price }}</span>
                                </td>
                                <td class="p-2 text-center align-middle bg-transparent border-b whitespace-nowrap shadow-transparent">
                                    <span class="font-semibold leading-tight text-xs text-gray-800">{{ formattedDate(t.created_at) }}</span>
                                </td>
                                <td class="p-2 align-middle bg-transparent border-b whitespace-nowrap shadow-transparent">
                                    <button @click="activate(t.stock_id)" type="button" class="inline-block px-6 py-3 font-bold text-center text-white uppercase align-middle transition-all rounded-lg cursor-pointer bg-gradient-to-tl from-blue-500 to-violet-500 leading-normal text-xs ease-in tracking-tight-rem shadow-xs bg-150 bg-x-25 hover:-translate-y-px active:opacity-85 hover:shadow-md">
                                        View
                                    </button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <nav class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-3 md:space-y-0 p-4" aria-label="Table navigation">
                    </nav>
                </div>
            </div>
        </div>

        <Company v-if="active !== null" :wallet="this.$page.props.userWallet" :ticker="active" @close="active = null" />
    </AuthenticatedLayout>
</template>
