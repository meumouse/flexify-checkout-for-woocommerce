jQuery(document).ready( function($) {
    /**
     * Get user IP from public API
     * 
     * @since 3.0.0
     * @return void
     */
    function get_user_ip() {
        // Check if we already have the information in the browser cache and if it is still valid
        var cachedCountry = localStorage.getItem('userCountry');
        var cachedTimestamp = localStorage.getItem('userCountryTimestamp');

        if (cachedCountry && cachedTimestamp) {
            var currentTime = new Date().getTime();
            var elapsedTime = currentTime - parseInt(cachedTimestamp);

            // If less than 24 hours have passed since the last update, use cache data
            if (elapsedTime < 24 * 60 * 60 * 1000) {
                $('#billing_country').val(cachedCountry).change();
                return;
            }
        }

        // If not in the cache or if the cache has expired, make a new request to obtain the IP address
        fetch(fcw_ip_api_params.get_ip)
            .then(response => response.json())
            .then(data => {
                get_country_info(data[fcw_ip_api_params.ip_param]);
            })
            .catch(error => {
                console.error('Error obtaining IP address: ', error);
            });
    }

     /**
     * Get country from IP
     * 
     * @since 3.0.0
     * @param {string} ip | IP address
     * @return void
     */
    function get_country_info(ip) {
        fetch(fcw_ip_api_params.get_country + ip)
            .then(response => response.json())
            .then(data => {
                // Store country information in the browser cache along with the current date and time
                localStorage.setItem('userCountry', data[fcw_ip_api_params.country_param]);
                localStorage.setItem('userCountryTimestamp', new Date().getTime());

                $('#billing_country').val(data[fcw_ip_api_params.country_param]).change();
            })
            .catch(error => {
                console.error('Error getting country information: ', error);
            });
    }

    // Call the function to get the user's IP address
    get_user_ip();
});