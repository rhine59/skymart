import Foundation

protocol MarketplaceService {
    func fetchListings(query: String?, category: String?) async throws -> [Listing]
}

protocol AuthenticationService {
    func login(email: String, password: String, deviceName: String) async throws -> APIUser
    func currentUser() async throws -> APIUser
    func logout() async throws
}

struct APIUser: Codable, Equatable {
    let id: Int
    let name: String
    let email: String
}

struct MockMarketplaceService: MarketplaceService {
    func fetchListings(query: String?, category: String?) async throws -> [Listing] {
        Listing.samples.filter { item in
            (category == nil || item.category == category) &&
            (query?.isEmpty != false || item.title.localizedCaseInsensitiveContains(query ?? "") ||
             item.location.localizedCaseInsensitiveContains(query ?? ""))
        }
    }
}

final class SkyMartAPI: MarketplaceService, AuthenticationService {
    let baseURL: URL
    let session: URLSession
    let tokens: KeychainTokenStore

    init(baseURL: URL, session: URLSession = .shared, tokens: KeychainTokenStore = .init()) {
        self.baseURL=baseURL; self.session=session; self.tokens=tokens
    }

    func fetchListings(query: String?, category: String?) async throws -> [Listing] {
        var components=URLComponents(url:baseURL.appending(path:"api/v1/listings"),resolvingAgainstBaseURL:false)!
        var items:[URLQueryItem]=[]
        if let query,!query.isEmpty { items.append(.init(name:"q",value:query)) }
        if let category { items.append(.init(name:"category",value:category.lowercased().replacingOccurrences(of:" ",with:"-"))) }
        components.queryItems=items.isEmpty ? nil:items
        let data=try await send(URLRequest(url:components.url!),authenticated:false)
        return try JSONDecoder().decode(ListingsEnvelope.self,from:data).data.map(Listing.init(api:))
    }

    func login(email:String,password:String,deviceName:String="iPhone") async throws -> APIUser {
        var request=URLRequest(url:baseURL.appending(path:"api/v1/auth/login"));request.httpMethod="POST"
        request.setValue("application/json",forHTTPHeaderField:"Content-Type")
        request.httpBody=try JSONEncoder().encode(LoginRequest(email:email,password:password,device_name:deviceName))
        let envelope=try JSONDecoder().decode(LoginEnvelope.self,from:try await send(request,authenticated:false))
        try tokens.save(envelope.data.token)
        return envelope.data.user
    }

    func currentUser() async throws -> APIUser {
        let data=try await send(URLRequest(url:baseURL.appending(path:"api/v1/me")),authenticated:true)
        return try JSONDecoder().decode(UserEnvelope.self,from:data).data
    }

    func logout() async throws {
        var request=URLRequest(url:baseURL.appending(path:"api/v1/auth/logout"));request.httpMethod="POST"
        defer { tokens.clear() }
        _=try await send(request,authenticated:true)
    }

    private func send(_ original:URLRequest,authenticated:Bool) async throws -> Data {
        var request=original
        if authenticated {
            guard let token=tokens.load() else { throw APIError.notAuthenticated }
            request.setValue("Bearer \(token)",forHTTPHeaderField:"Authorization")
        }
        let (data,response)=try await session.data(for:request)
        guard let http=response as? HTTPURLResponse else { throw URLError(.badServerResponse) }
        if http.statusCode==401 { if authenticated { tokens.clear() }; throw APIError.notAuthenticated }
        guard 200..<300 ~= http.statusCode else { throw APIError.http(http.statusCode) }
        return data
    }
}

enum APIError: Error { case notAuthenticated; case http(Int) }
private struct LoginRequest:Encodable { let email:String;let password:String;let device_name:String }
private struct LoginEnvelope:Decodable { let data:LoginData }
private struct LoginData:Decodable { let token:String;let expires_at:String;let user:APIUser }
private struct UserEnvelope:Decodable { let data:APIUser }
private struct ListingsEnvelope:Decodable { let data:[APIListing] }
private struct APIListing:Decodable {
    let id:Int;let title:String;let price_gbp:Double?;let location:String?;let description:String
    let category:APICategory;let seller:APISeller
}
private struct APICategory:Decodable { let name:String }
private struct APISeller:Decodable { let name:String }

private extension Listing {
    init(api:APIListing) {
        self.init(id:UUID(),title:api.title,category:api.category.name,price:Int(api.price_gbp ?? 0),
                  location:api.location ?? "",details:api.description,seller:api.seller.name,symbol:"airplane")
    }
}
