import Foundation

@MainActor
final class AccountSession: ObservableObject {
    @Published var user: APIUser?
    @Published var isLoading = false
    @Published var message: String?
    let api: SkyMartAPI

    init(api: SkyMartAPI) { self.api=api }

    func restore() async {
        guard api.tokens.load() != nil else { return }
        isLoading=true; defer { isLoading=false }
        do { user=try await api.currentUser() } catch { user=nil }
    }
    func login(email:String,password:String) async {
        isLoading=true;message=nil;defer{isLoading=false}
        do { user=try await api.login(email:email,password:password,deviceName:"iPhone");message=nil }
        catch { message="Unable to sign in. Check your email and password." }
    }
    func logout() async {
        isLoading=true;defer{isLoading=false}
        do { try await api.logout() } catch { api.tokens.clear() }
        user=nil
    }
    func requestReset(email:String) async {
        isLoading=true;defer{isLoading=false}
        do { try await api.requestPasswordReset(email:email);message="If that address has a SkyMart account, a reset email has been sent." }
        catch { message="Unable to request a reset right now." }
    }
    func changePassword(current:String,new:String) async -> Bool {
        isLoading=true;defer{isLoading=false}
        do { try await api.changePassword(current:current,new:new);user=nil;message="Password changed. Please sign in again.";return true }
        catch { message="Password could not be changed.";return false }
    }
}
