import api from './api';

export const travelService = {
  async searchTrains(from = 'NDLS - New Delhi', to = 'BSB - Varanasi Junction', date = '', quota = 'GN') {
    const res = await api.get(`/travel/trains.php?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}&date=${encodeURIComponent(date)}&quota=${encodeURIComponent(quota)}`);
    return res.data;
  },

  async searchBuses(from = 'Delhi', to = 'Jaipur', date = '') {
    const res = await api.get(`/travel/buses.php?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}&date=${encodeURIComponent(date)}`);
    return res.data;
  },

  async searchFlights(from = 'DEL - New Delhi', to = 'BOM - Mumbai', date = '') {
    const res = await api.get(`/travel/flights.php?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}&date=${encodeURIComponent(date)}`);
    return res.data;
  },

  async searchHotels(city = 'Goa', checkin = '', checkout = '') {
    const res = await api.get(`/travel/hotels.php?city=${encodeURIComponent(city)}&checkin=${encodeURIComponent(checkin)}&checkout=${encodeURIComponent(checkout)}`);
    return res.data;
  },

  async createBooking(bookingData) {
    const res = await api.post('/bookings/create.php', bookingData);
    return res.data;
  },

  async listBookings(category = 'all') {
    const res = await api.get(`/bookings/list.php?category=${encodeURIComponent(category)}`);
    return res.data;
  },

  async getDetails(ref) {
    const res = await api.get(`/bookings/details.php?ref=${encodeURIComponent(ref)}`);
    return res.data;
  },

  async cancelBooking(bookingReference) {
    const res = await api.post('/bookings/cancel.php', {
      booking_reference: bookingReference
    });
    return res.data;
  }
};

export default travelService;
