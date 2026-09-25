import SwiftUI

struct FavouritesView: View {
    @EnvironmentObject var store: MarketplaceStore
    var favourites: [Listing] { store.listings.filter(\.isFavourite) }

    var body: some View {
        List(favourites) { listing in
            NavigationLink(value: listing) { ListingRow(listing: listing) }
        }
        .overlay { if favourites.isEmpty { ContentUnavailableView("No favourites", systemImage: "heart", description: Text("Save adverts while browsing and they will appear here.")) } }
        .navigationTitle("Favourites")
        .navigationDestination(for: Listing.self) { ListingDetailView(listingID: $0.id) }
    }
}
