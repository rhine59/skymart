import SwiftUI

struct FavouritesView: View {
    @EnvironmentObject var account: AccountSession
    @State private var favourites: [Listing] = []
    @State private var searches: [SavedSearch] = []
    @State private var error: String?
    @State private var editing: SavedSearch?
    @State private var showEditor = false
    @State private var busy = false
    @State private var matching: [Listing] = []
    @State private var showMatches = false

    var body: some View {
        List {
            if account.user == nil {
                ContentUnavailableView("Sign in to save adverts", systemImage: "person.crop.circle", description: Text("Favourites and saved searches sync with your SkyMart account."))
            } else {
                Section("Favourites") {
                    if favourites.isEmpty { Text("No favourite adverts yet").foregroundStyle(.secondary) }
                    ForEach(favourites) { item in
                        HStack {
                            VStack(alignment: .leading) {
                                Text(item.title).font(.headline)
                                Text(item.location).font(.caption).foregroundStyle(.secondary)
                            }
                            Spacer()
                            Button("Remove") { Task { await removeFavourite(item) } }.buttonStyle(.borderless)
                        }
                    }
                }
                Section("Saved searches") {
                    ForEach(searches) { search in
                        VStack(alignment: .leading, spacing: 8) {
                            Text(search.name).font(.headline)
                            Text([search.query_text, search.category_slug].filter { !$0.isEmpty }.joined(separator: " · ")).font(.caption).foregroundStyle(.secondary)
                            HStack {
                                Button("Run") { Task { await run(search) } }
                                Button("Edit") { editing = search; showEditor = true }
                                Button("Delete", role: .destructive) { Task { await delete(search) } }
                            }.buttonStyle(.borderless)
                        }
                    }
                    Button("New saved search", systemImage: "plus") { editing = nil; showEditor = true }
                }
                Section { Text("Email, SMS and WhatsApp notifications are not yet available.").font(.caption).foregroundStyle(.secondary) }
            }
        }
        .navigationTitle("Saved")
        .toolbar { Button("Refresh", systemImage: "arrow.clockwise") { Task { await refresh() } }.disabled(account.user == nil || busy) }
        .task(id: account.user?.id) { await refresh() }
        .refreshable { await refresh() }
        .sheet(isPresented: $showMatches) { NavigationStack { List(matching) { item in VStack(alignment: .leading) { Text(item.title).font(.headline); Text(item.location).foregroundStyle(.secondary); Text(item.price, format: .currency(code: "GBP")) } }.navigationTitle("Search results").toolbar { Button("Done") { showMatches = false } } } }
        .sheet(isPresented: $showEditor, onDismiss: { Task { await refresh() } }) { NavigationStack { SavedSearchEditor(search: editing, api: account.api) } }
        .alert("SkyMart", isPresented: Binding(get: { error != nil }, set: { if !$0 { error = nil } })) { Button("OK", role: .cancel) {} } message: { Text(error ?? "") }
    }
    private func refresh() async {
        guard account.user != nil else { favourites = []; searches = []; return }
        busy = true; defer { busy = false }
        do { favourites = try await account.api.fetchFavourites(); searches = try await account.api.fetchSavedSearches() }
        catch { self.error = "Unable to load saved items. Please try again." }
    }
    private func removeFavourite(_ listing: Listing) async {
        guard let id = listing.serverID else { return }
        do { try await account.api.setFavourite(listingID: id, enabled: false); await refresh() }
        catch { self.error = "Unable to remove favourite." }
    }
    private func delete(_ search: SavedSearch) async {
        do { try await account.api.deleteSearch(id: search.id); await refresh() }
        catch { self.error = "Unable to delete saved search." }
    }
    private func run(_ search: SavedSearch) async {
        do {
            let items = try await account.api.fetchListings(query: search.query_text, category: search.category_slug.isEmpty ? nil : search.category_slug)
            matching = items
            showMatches = true
        } catch { self.error = "Unable to run saved search." }
    }
}

private struct SavedSearchEditor: View {
    @Environment(\.dismiss) private var dismiss
    let search: SavedSearch?
    let api: SkyMartAPI
    @State private var name: String = ""
    @State private var query: String = ""
    @State private var category: String = ""
    @State private var enabled = true
    @State private var frequency = "daily"
    @State private var error: String?
    @State private var saving = false
    private let categories = ["", "aircraft", "engines", "propellers", "avionics", "instruments", "parts", "pilot-equipment", "miscellaneous"]

    var body: some View {
        Form {
            Section("Search") {
                TextField("Name", text: $name)
                TextField("Keywords", text: $query)
                Picker("Category", selection: $category) { ForEach(categories, id: \.self) { Text($0.isEmpty ? "All categories" : $0.capitalized).tag($0) } }
                Toggle("Enabled", isOn: $enabled)
                Picker("Frequency", selection: $frequency) { Text("Immediate").tag("immediate"); Text("Daily").tag("daily"); Text("Weekly").tag("weekly") }
            }
            Section("Notifications") { Text("Delivery by email, text and WhatsApp will be enabled after destination verification and consent are configured.").font(.caption).foregroundStyle(.secondary) }
            if let error { Section { Text(error).foregroundStyle(.red) } }
        }
        .navigationTitle(search == nil ? "New search" : "Edit search")
        .toolbar {
            ToolbarItem(placement: .cancellationAction) { Button("Cancel") { dismiss() } }
            ToolbarItem(placement: .confirmationAction) { Button("Save") { Task { await save() } }.disabled(name.trimmingCharacters(in: .whitespaces).isEmpty || saving) }
        }
        .onAppear {
            if let search { name = search.name; query = search.query_text; category = search.category_slug; enabled = search.enabled != 0; frequency = search.frequency }
        }
    }
    private func save() async {
        saving = true; defer { saving = false }
        do { try await api.saveSearch(id: search?.id, name: name, query: query, category: category, enabled: enabled, frequency: frequency); dismiss() }
        catch { self.error = "Unable to save search. Check the details and try again." }
    }
}
