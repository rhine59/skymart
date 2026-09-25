import SwiftUI

struct SellView: View {
    @EnvironmentObject var store: MarketplaceStore
    @State private var title = ""
    @State private var category = "Aircraft"
    @State private var price = ""
    @State private var location = ""
    @State private var details = ""
    @State private var showingPreview = false

    var body: some View {
        Form {
            Section("Advert") {
                TextField("Title", text: $title)
                Picker("Category", selection: $category) { ForEach(store.categories) { Text($0.name).tag($0.name) } }
                TextField("Price (£)", text: $price).keyboardType(.numberPad)
                TextField("Location", text: $location)
                TextField("Description", text: $details, axis: .vertical).lineLimit(4...8)
            }
            Section("Photos") {
                Label("Photo picker arrives with secure image upload", systemImage: "photo.badge.plus").foregroundStyle(.secondary)
            }
            Button("Preview advert") { showingPreview = true }
                .disabled(title.isEmpty || Int(price) == nil || location.isEmpty || details.isEmpty)
        }
        .navigationTitle("Sell")
        .sheet(isPresented: $showingPreview) {
            NavigationStack {
                List {
                    ListingRow(listing: .init(id: UUID(), title: title, category: category, price: Int(price) ?? 0, location: location, details: details, seller: "You", symbol: store.categories.first(where: {$0.name == category})?.symbol ?? "shippingbox"))
                    Section("Description") { Text(details) }
                    Button("Publish prototype advert") {
                        store.add(title: title, category: category, price: Int(price) ?? 0, location: location, details: details)
                        title=""; price=""; location=""; details=""; showingPreview=false
                    }.buttonStyle(.borderedProminent)
                }.navigationTitle("Preview")
            }
        }
    }
}
