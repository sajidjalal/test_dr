import React, { useState } from "react";
import axios from "axios";

const MessageInput = ({ rootUrl, chatId, onSent }) => {
  const [message, setMessage] = useState("");
  const [fileData, setFileData] = useState(null);

  const sendMessage = async (e) => {
    e.preventDefault();
    if (!message.trim()) return;

    try {
      const header = {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      }
      const formData = new FormData();
      formData.append("text", message);
      if(fileData){
        console.log(fileData);
        formData.append("attachment", fileData);
      }
      await axios.post(`${rootUrl}/chats/${chatId}/messages`, formData, header);
      setMessage("");
      onSent();
    } catch (err) {
      console.error(err);
    }
  };

  return (
    <form className="d-flex" onSubmit={sendMessage}>
      <input
        type="text"
        className="form-control me-2"
        placeholder="Type a message..."
        value={message}
        onChange={(e) => setMessage(e.target.value)}
      />
      <input
        type="file"
        className="form-control me-2"
        name="attachment"
        onChange={(e) => setFileData(e.target.files[0])}
      />
      <button className="btn btn-primary" type="submit">Send</button>
    </form>
  );
};

export default MessageInput;
