import Foundation

protocol MarketplaceService {
    func fetchListings(query: String?, category: String?) async throws -> [Listing]
}

struct MockMarketplaceService: MarketplaceService {
    func fetchListings(query: String?, category: String?) async throws -> [Listing] {
        Listing.samples.filter { item in
            (category == nil || item.category == category) &&
            (query?.isEmpty != false ||
             item.title.localizedCaseInsensitiveContains(query ?? "") ||
             item.location.localizedCaseInsensitiveContains(query ?? ""))
        }
    }
}

struct APIMarketplaceService: MarketplaceService {
    let baseURL: URL
    let session: URLSession

    init(baseURL: URL, session: URLSession = .shared) {
        self.baseURL = baseURL
        self.session = session
    }

    func fetchListings(query: String?, category: String?) async throws -> [Listing] {
        var components = URLComponents(url: baseURL.appending(path: "api/v1/listings"), resolvingAgainstBaseURL: false)!
        var items: [URLQueryItem] = []
        if let query, !query.isEmpty { items.append(.init(name: "q", value: query)) }
        if let category { items.append(.init(name: "category", value: category.lowercased().replacingOccurrences(of: " ", with: "-"))) }
        components.queryItems = items.isEmpty ? nil : items

        let (data, response) = try await session.data(from: components.url!)
        guard let http = response as? HTTPURLResponse, 200..<300 ~= http.statusCode else {
            throw URLError(.badServerResponse)
        }
        let envelope = try JSONDecoder().decode(ListingsEnvelope.self, from: data)
        return envelope.data.map(Listing.init(api:))
    }
}

private struct ListingsEnvelope: Decodable { let data: [APIListing] }
private struct APIListing: Decodable {
    let id: Int
    let title: String
    let price_gbp: Int
    let location: String
    let description: String
    let category: APICategory
    let seller: APISeller
}
private struct APICategory: Decodable { let name: String }
private struct APISeller: Decodable { let name: String }

private extension Listing {
    init(api: APIListing) {
        self.init(id: UUID(), title: api.title, category: api.category.name, price: api.price_gbp,
                  location: api.location, details: api.description, seller: api.seller.name,
                  symbol: "airplane")
    }
}
