import SwiftUI
import PhotosUI
import UIKit

struct SellView:View {
 @EnvironmentObject var store:MarketplaceStore
 @EnvironmentObject var account:AccountSession
 @State private var title = ""
 @State private var category = "Aircraft"
 @State private var price = ""
 @State private var location = ""
 @State private var details = ""
 @State private var picks:[PhotosPickerItem]=[];@State private var photos:[SelectedPhoto]=[]
 @State private var showingCamera=false
 @State private var publishing=false;@State private var progress=0.0;@State private var message:String?

 var body:some View {ScrollView{VStack(spacing:18){
  PhotosPicker(selection:$picks,maxSelectionCount:max(0,10-photos.count),matching:.images){Label(photos.isEmpty ? "Choose from Photos albums":"Add from Photos albums",systemImage:"photo.on.rectangle.angled").font(.headline).frame(maxWidth:.infinity).padding(28).background(Color.blue.opacity(0.08)).clipShape(RoundedRectangle(cornerRadius:20))}
  if UIImagePickerController.isSourceTypeAvailable(.camera){Button{showingCamera=true}label:{Label("Take photo",systemImage:"camera")}.buttonStyle(.bordered)}
  if !photos.isEmpty{ScrollView(.horizontal){HStack{ForEach(photos){p in ZStack(alignment:.topTrailing){Image(uiImage:p.image).resizable().scaledToFill().frame(width:110,height:90).clipped().clipShape(RoundedRectangle(cornerRadius:12));Button{photos.removeAll{$0.id==p.id}}label:{Image(systemName:"xmark.circle.fill").symbolRenderingMode(.palette).foregroundStyle(.white,.black.opacity(0.65))}.padding(4)}}}}}
  VStack(spacing:14){field("Advert title",text:$title);Picker("Category",selection:$category){ForEach(store.categories){Text($0.name).tag($0.name)}}.pickerStyle(.menu);field("Price (£)",text:$price).keyboardType(.numberPad);field("Location",text:$location);TextField("Describe condition, history and what's included",text:$details,axis:.vertical).lineLimit(5...8).padding().background(Color(.secondarySystemGroupedBackground)).clipShape(RoundedRectangle(cornerRadius:12))}
  if publishing{ProgressView(value:progress){Text("Publishing advert…")}}
  if let message{Text(message).font(.caption).foregroundStyle(.secondary)}
  Button{Task{await publish()}}label:{Label("Publish advert",systemImage:"paperplane.fill").frame(maxWidth:.infinity)}.buttonStyle(.borderedProminent).controlSize(.large).disabled(!valid||publishing||account.user==nil)
  if account.user==nil{Text("Sign in from the Account tab before publishing.").font(.caption).foregroundStyle(.secondary)}
 }.padding()}.background(Color(.systemGroupedBackground)).navigationTitle("Sell")
 .onChange(of:picks){_,items in Task{await load(items);picks=[]}}
 .sheet(isPresented:$showingCamera){CameraPicker { image in addPhoto(image) }}
 }

 private var valid:Bool{!title.isEmpty && Int(price) != nil && !location.isEmpty && details.count>=10}
 @MainActor private func addPhoto(_ image:UIImage){guard photos.count<10,let jpeg=image.jpegData(compressionQuality:0.85) else{return};photos.append(.init(image:image,jpeg:jpeg))}
 private func load(_ items:[PhotosPickerItem]) async {for item in items where photos.count<10{if let data=try? await item.loadTransferable(type:Data.self),let image=UIImage(data:data),let jpeg=image.jpegData(compressionQuality:0.88){photos.append(.init(image:image,jpeg:jpeg))}}}
 @MainActor private func publish() async {
  guard let value=Int(price),valid else{return};publishing=true;progress=0;message=nil;defer{publishing=false}
  do{let listing=try await account.api.createListing(title:title,category:category,price:value,location:location,description:details);guard let id=listing.serverID else{throw APIError.http(500)}
   for (i,p) in photos.enumerated(){try await account.api.uploadJPEG(p.jpeg,to:id);progress=Double(i+1)/Double(max(photos.count,1))}
   store.listings.insert(listing,at:0);title="";price="";location="";details="";photos=[];progress=1;message="Advert published."
  }catch{message="The advert could not be published. Please try again."}
 }
 @ViewBuilder private func field(_ prompt:String,text:Binding<String>)->some View{TextField(prompt,text:text).textFieldStyle(.plain).padding().background(Color(.secondarySystemGroupedBackground)).clipShape(RoundedRectangle(cornerRadius:12))}
}

private struct SelectedPhoto:Identifiable {let id=UUID();let image:UIImage;let jpeg:Data}

private struct CameraPicker:UIViewControllerRepresentable {
 let onPhoto:(UIImage)->Void
 @Environment(\.dismiss) private var dismiss
 func makeUIViewController(context:Context)->UIImagePickerController {let picker=UIImagePickerController();picker.sourceType = .camera;picker.delegate=context.coordinator;return picker}
 func updateUIViewController(_ controller:UIImagePickerController,context:Context){}
 func makeCoordinator()->Coordinator {Coordinator(self)}
 final class Coordinator:NSObject,UIImagePickerControllerDelegate,UINavigationControllerDelegate {
  let parent:CameraPicker
  init(_ parent:CameraPicker){self.parent=parent}
  func imagePickerController(_ picker:UIImagePickerController,didFinishPickingMediaWithInfo info:[UIImagePickerController.InfoKey:Any]){if let image=info[.originalImage] as? UIImage{parent.onPhoto(image)};parent.dismiss()}
  func imagePickerControllerDidCancel(_ picker:UIImagePickerController){parent.dismiss()}
 }
}
