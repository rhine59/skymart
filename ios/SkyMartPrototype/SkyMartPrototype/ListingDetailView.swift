import SwiftUI

struct ListingDetailView: View {
    @EnvironmentObject var store: MarketplaceStore
    @EnvironmentObject var account: AccountSession
    @State private var favouriteError: String?
    let listingID: UUID

    private var listing: Listing? { store.listings.first { $0.id == listingID } }

    var body: some View {
        Group {
            if let listing {
                ScrollView {
                    VStack(alignment: .leading, spacing: 18) {
                        gallery(for: listing)
                        HStack(alignment: .top) {
                            VStack(alignment: .leading, spacing: 5) {
                                Text(listing.title).font(.largeTitle.bold())
                                Label(listing.location, systemImage: "mappin.and.ellipse").foregroundStyle(.secondary)
                            }
                            Spacer()
                            Button { Task {
                                guard account.user != nil else { favouriteError="Sign in to save favourites."; return }
                                guard let id = listing.serverID else { favouriteError="Advert unavailable."; return }
                                do { try await account.api.setFavourite(listingID: id, enabled: !listing.isFavourite); store.toggleFavourite(listing) }
                                catch { favouriteError="Unable to update favourite." }
                            } } label: {
                                Image(systemName: listing.isFavourite ? "heart.fill" : "heart")
                                    .font(.title2)
                                    .foregroundStyle(listing.isFavourite ? .red : .primary)
                            }
                        }
                        Text(listing.price, format: .currency(code: "GBP").precision(.fractionLength(0)))
                            .font(.title.bold())
                        Divider()
                        Text("About this advert").font(.title3.bold())
                        Text(listing.details)
                        Divider()
                        Label(listing.seller, systemImage: "person.crop.circle.fill")
                        Button {} label: {
                            Label("Contact seller", systemImage: "message.fill").frame(maxWidth: .infinity)
                        }
                        .buttonStyle(.borderedProminent)
                        .controlSize(.large)
                        .disabled(true)
                    }
                    .padding()
                }
            } else {
                ContentUnavailableView("Advert unavailable", systemImage: "exclamationmark.triangle")
            }
        }
        .alert("Favourites", isPresented: Binding(get: { favouriteError != nil }, set: { if !$0 { favouriteError = nil } })) { Button("OK", role: .cancel) {} } message: { Text(favouriteError ?? "") }
        .navigationTitle("Advert")
        .navigationBarTitleDisplayMode(.inline)
    }

    @ViewBuilder
    private func gallery(for listing: Listing) -> some View {
        Group {
            if listing.images.isEmpty {
                ZStack {
                    LinearGradient(colors: [.gray.opacity(0.18), .gray.opacity(0.38)], startPoint: .top, endPoint: .bottom)
                    Image(systemName: listing.symbol)
                        .font(.system(size: 100, weight: .ultraLight))
                        .foregroundStyle(.secondary)
                }
            } else {
                TabView {
                    ForEach(Array(listing.images.enumerated()), id: \.offset) { _, image in
                        AsyncImage(url: image.url) { phase in
                            switch phase {
                            case .success(let picture): picture.resizable().scaledToFit()
                            case .failure: ContentUnavailableView("Image unavailable", systemImage: "photo")
                            default: ProgressView()
                            }
                        }
                    }
                }
                .tabViewStyle(.page(indexDisplayMode: .automatic))
            }
        }
        .frame(height: 300)
        .clipShape(RoundedRectangle(cornerRadius: 22))
    }
}
