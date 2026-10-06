import XCTest
@testable import SkyMartPrototype

@MainActor
final class MarketplaceStoreTests: XCTestCase {
    func testSearch() {
        let store = MarketplaceStore()
        store.query = "Trig"
        XCTAssertEqual(store.filtered.count, 1)
        XCTAssertEqual(store.filtered.first?.category, "Avionics")
    }

    func testCategoryFilter() {
        let store = MarketplaceStore()
        store.selectedCategory = "Aircraft"
        XCTAssertTrue(store.filtered.allSatisfy { $0.category == "Aircraft" })
    }

    func testCreateAdvert() {
        let store = MarketplaceStore()
        let count = store.listings.count
        store.add(title: "Test Propeller", category: "Propellers", price: 500, location: "UK", details: "Test")
        XCTAssertEqual(store.listings.count, count + 1)
        XCTAssertEqual(store.listings.first?.seller, "You")
    }
}
