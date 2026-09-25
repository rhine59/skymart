import Foundation

@MainActor
final class MarketplaceStore: ObservableObject {
    @Published var listings = Listing.samples
    @Published var query = ""
    @Published var selectedCategory: String?

    let categories = [
        Category(name: "Aircraft", symbol: "airplane"),
        Category(name: "Engines", symbol: "engine.combustion"),
        Category(name: "Propellers", symbol: "fanblades"),
        Category(name: "Avionics", symbol: "antenna.radiowaves.left.and.right"),
        Category(name: "Instruments", symbol: "gauge.with.dots.needle.67percent"),
        Category(name: "Parts", symbol: "wrench.and.screwdriver"),
        Category(name: "Pilot Equipment", symbol: "headphones"),
        Category(name: "Miscellaneous", symbol: "shippingbox")
    ]

    var filtered: [Listing] {
        listings.filter { item in
            (selectedCategory == nil || item.category == selectedCategory) &&
            (query.isEmpty || item.title.localizedCaseInsensitiveContains(query) ||
             item.location.localizedCaseInsensitiveContains(query))
        }
    }

    func toggleFavourite(_ listing: Listing) {
        guard let i = listings.firstIndex(where: { $0.id == listing.id }) else { return }
        listings[i].isFavourite.toggle()
    }

    func add(title: String, category: String, price: Int, location: String, details: String) {
        listings.insert(.init(id: UUID(), title: title, category: category, price: price,
                              location: location, details: details, seller: "You",
                              symbol: categories.first(where: {$0.name == category})?.symbol ?? "shippingbox"), at: 0)
    }

    func reset() { listings = Listing.samples; query = ""; selectedCategory = nil }
}
