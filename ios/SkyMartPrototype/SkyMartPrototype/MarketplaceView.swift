import SwiftUI

struct MarketplaceView: View {
    @EnvironmentObject var store: MarketplaceStore

    var body: some View {
        List {
            Section {
                ScrollView(.horizontal, showsIndicators: false) {
                    HStack {
                        Button { store.selectedCategory = nil } label: { CategoryChip(name: "All", symbol: "square.grid.2x2", selected: store.selectedCategory == nil) }
                        ForEach(store.categories) { category in
                            Button { store.selectedCategory = category.name } label: {
                                CategoryChip(name: category.name, symbol: category.symbol, selected: store.selectedCategory == category.name)
                            }
                        }
                    }.padding(.vertical, 4)
                }
            }
            Section(store.selectedCategory ?? "Latest adverts") {
                if store.filtered.isEmpty {
                    ContentUnavailableView("No adverts found", systemImage: "magnifyingglass")
                } else {
                    ForEach(store.filtered) { listing in
                        NavigationLink(value: listing) { ListingRow(listing: listing) }
                    }
                }
            }
        }
        .navigationTitle("SkyMart")
        .searchable(text: $store.query, prompt: "Aircraft, avionics, parts…")
        .navigationDestination(for: Listing.self) { ListingDetailView(listingID: $0.id) }
    }
}

struct CategoryChip: View {
    let name: String; let symbol: String; let selected: Bool
    var body: some View {
        VStack(spacing: 6) {
            Image(systemName: symbol).font(.title2)
            Text(name).font(.caption).lineLimit(1)
        }
        .padding(10).frame(minWidth: 82)
        .background(selected ? Color.accentColor.opacity(0.18) : Color.secondary.opacity(0.08))
        .clipShape(RoundedRectangle(cornerRadius: 12))
    }
}

struct ListingRow: View {
    let listing: Listing
    var body: some View {
        HStack(spacing: 14) {
            Image(systemName: listing.symbol).font(.title).frame(width: 52, height: 52).background(.thinMaterial).clipShape(RoundedRectangle(cornerRadius: 10))
            VStack(alignment: .leading, spacing: 3) {
                Text(listing.title).font(.headline)
                Text(listing.category + " • " + listing.location).font(.caption).foregroundStyle(.secondary)
                Text(listing.price, format: .currency(code: "GBP").precision(.fractionLength(0))).fontWeight(.semibold)
            }
            Spacer()
            if listing.isFavourite { Image(systemName: "heart.fill").foregroundStyle(.red) }
        }
    }
}
