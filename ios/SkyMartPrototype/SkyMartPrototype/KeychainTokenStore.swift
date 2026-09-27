import Foundation
import Security

final class KeychainTokenStore {
    private let service = "uk.skymart.prototype"
    private let account = "api-token"

    func save(_ token: String) throws {
        let data = Data(token.utf8)
        let query: [String: Any] = [kSecClass as String:kSecClassGenericPassword,
                                    kSecAttrService as String:service,
                                    kSecAttrAccount as String:account]
        SecItemDelete(query as CFDictionary)
        var add = query
        add[kSecValueData as String] = data
        add[kSecAttrAccessible as String] = kSecAttrAccessibleAfterFirstUnlockThisDeviceOnly
        let status = SecItemAdd(add as CFDictionary,nil)
        guard status == errSecSuccess else { throw KeychainError.status(status) }
    }

    func load() -> String? {
        let query: [String: Any] = [kSecClass as String:kSecClassGenericPassword,
                                    kSecAttrService as String:service,
                                    kSecAttrAccount as String:account,
                                    kSecReturnData as String:true,
                                    kSecMatchLimit as String:kSecMatchLimitOne]
        var item: CFTypeRef?
        guard SecItemCopyMatching(query as CFDictionary,&item) == errSecSuccess,
              let data=item as? Data else { return nil }
        return String(data:data,encoding:.utf8)
    }

    func clear() {
        SecItemDelete([kSecClass as String:kSecClassGenericPassword,
                       kSecAttrService as String:service,
                       kSecAttrAccount as String:account] as CFDictionary)
    }

    enum KeychainError: Error { case status(OSStatus) }
}
